<?php

namespace App\Services;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Genera database/production_schema.sql a partir de las migraciones y el
 * DatabaseSeeder reales, para importarlo en hosting compartido (phpMyAdmin).
 *
 * - El DDL se obtiene con la gramática MySQL de Laravel en modo "pretend"
 *   (no requiere un servidor MySQL) y los índices/llaves foráneas se integran
 *   dentro de cada CREATE TABLE.
 * - Los datos iniciales se obtienen ejecutando el seeder en SQLite en memoria.
 * - La salida es determinista para poder verificar su sincronización en pruebas.
 */
class ProductionSqlExporter
{
    public const DEFAULT_PATH = 'database/production_schema.sql';

    /** Hash bcrypt fijo de la contraseña inicial "password" (salida determinista). */
    private const ADMIN_PASSWORD_HASH = '$2y$12$BgxhT8xgUFN4AVJ1.9MKSOv2Ze1KE5j0ImhwV2wn.TvsFGXLf7XY2';

    /** Tablas cuyos registros sembrados se exportan como catálogos iniciales. */
    private const DATA_TABLES = [
        'roles', 'permissions', 'role_permission', 'users',
        'site_settings', 'coverage_zones', 'categories', 'services', 'service_features',
    ];

    /** Orden de exportación para tablas sin columna `id` (pivotes). */
    private const ORDER_COLUMNS = [
        'role_permission' => ['role_id', 'permission_id'],
    ];

    private const TIMESTAMP_COLUMNS = ['created_at', 'updated_at', 'email_verified_at'];

    private const DDL_CONNECTION = 'production_sql_ddl';

    private const SEED_CONNECTION = 'production_sql_seed';

    public function __construct(private readonly DatabaseManager $db) {}

    public function export(): string
    {
        $tables = $this->buildTables();

        $sql = [
            '-- ============================================================================',
            '-- Script SQL maestro de producción (instalación limpia desde cero)',
            '-- Compatible con MySQL 8.0+ / MariaDB 10.6+',
            '--',
            '-- ARCHIVO GENERADO: no lo edites a mano. Regenera con:',
            '--     php artisan db:export-production-sql',
            '-- La prueba ProductionSchemaSqlTest falla si este archivo no coincide con',
            '-- las migraciones y el DatabaseSeeder actuales.',
            '-- ============================================================================',
            '',
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS = 0;',
            '',
        ];

        foreach ($tables as $table => $definition) {
            $sql[] = "-- Tabla: {$table}";
            $sql[] = $definition;
            $sql[] = '';
        }

        $sql[] = '-- ============================================================================';
        $sql[] = '-- Datos iniciales';
        $sql[] = '-- Credenciales del administrador: admin@colectivomusical.mx / password';
        $sql[] = '-- IMPORTANTE: cambia la contraseña inmediatamente después del primer acceso.';
        $sql[] = '-- ============================================================================';
        $sql[] = '';

        foreach ($this->buildInserts() as $insert) {
            $sql[] = $insert;
            $sql[] = '';
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return implode("\n", $sql)."\n";
    }

    public function write(string $path): void
    {
        file_put_contents($path, $this->export());
    }

    /**
     * @return array<string, string> CREATE TABLE por tabla, ordenadas por dependencias.
     */
    private function buildTables(): array
    {
        $parsed = [];
        $dependencies = [];

        foreach ($this->pretendStatements() as $statement) {
            if (preg_match('/^create table `(\w+)` \((.*)\) default character set \w+ collate \'\w+\' engine = InnoDB$/s', $statement, $m)) {
                $parsed[$m[1]] = $this->splitTopLevel($m[2]);
                $dependencies[$m[1]] ??= [];

                continue;
            }

            // Columna eliminada en una migración posterior: se quita de la definición.
            if (preg_match('/^alter table `(\w+)` drop `(\w+)`$/', $statement, $m) && isset($parsed[$m[1]])) {
                $before = count($parsed[$m[1]]);
                $parsed[$m[1]] = array_values(array_filter($parsed[$m[1]], fn ($line) => ! str_starts_with($line, "`{$m[2]}` ")));
                if (count($parsed[$m[1]]) === $before) {
                    throw new RuntimeException("No se encontró la columna {$m[2]} en {$m[1]} para eliminarla.");
                }

                continue;
            }

            if (! preg_match('/^alter table `(\w+)` add (.+)$/s', $statement, $m) || ! isset($parsed[$m[1]])) {
                throw new RuntimeException("Sentencia no soportada por el exportador SQL: {$statement}");
            }

            [$table, $clause] = [$m[1], $m[2]];

            if (preg_match('/^unique `(\w+)`\((.+)\)$/', $clause, $c)) {
                $parsed[$table][] = "UNIQUE KEY `{$c[1]}` ({$c[2]})";
            } elseif (preg_match('/^index `(\w+)`\((.+)\)$/', $clause, $c)) {
                $parsed[$table][] = "KEY `{$c[1]}` ({$c[2]})";
            } elseif (preg_match('/^constraint `(\w+)` foreign key \((.+?)\) references `(\w+)` \((.+?)\)(.*)$/', $clause, $c)) {
                $parsed[$table][] = "CONSTRAINT `{$c[1]}` FOREIGN KEY ({$c[2]}) REFERENCES `{$c[3]}` ({$c[4]}){$c[5]}";
                if ($c[3] !== $table) {
                    $dependencies[$table][] = $c[3];
                }
            } elseif (preg_match('/^`(\w+)` (.+)$/s', $clause, $c)) {
                // Columna agregada en una migración posterior: se integra al CREATE TABLE.
                $parsed[$table] = $this->insertColumn($parsed[$table], "`{$c[1]}` {$c[2]}");
            } else {
                throw new RuntimeException("Cláusula ALTER no soportada por el exportador SQL: {$statement}");
            }
        }

        $tables = [];
        foreach ($this->sortByDependencies($dependencies) as $table) {
            $columns = implode(",\n", array_map(fn ($line) => "  {$line}", $parsed[$table]));
            $tables[$table] = "CREATE TABLE `{$table}` (\n{$columns}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }

        return $tables;
    }

    /**
     * Sentencias MySQL que ejecutarían las migraciones (sin conectarse a un servidor).
     *
     * @return list<string>
     */
    private function pretendStatements(): array
    {
        config(['database.connections.'.self::DDL_CONNECTION => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'production',
            'username' => 'production',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ]]);

        $connection = $this->db->connection(self::DDL_CONNECTION);
        $previousDefault = $this->db->getDefaultConnection();
        $statements = [];

        try {
            $this->db->setDefaultConnection(self::DDL_CONNECTION);

            $repository = new DatabaseMigrationRepository($this->db, config('database.migrations.table', 'migrations'));
            $repository->setSource(self::DDL_CONNECTION);
            $queries = $connection->pretend(fn () => $repository->createRepository());

            $files = glob(database_path('migrations/*.php'));
            sort($files);

            foreach ($files as $file) {
                $migration = require $file;
                $queries = [...$queries, ...$connection->pretend(fn () => $migration->up())];
            }

            foreach ($queries as $query) {
                $statements[] = trim($query['query']);
            }
        } finally {
            $this->db->setDefaultConnection($previousDefault);
            $this->db->purge(self::DDL_CONNECTION);
        }

        return $statements;
    }

    /**
     * Ejecuta migraciones y seeder en SQLite en memoria y los convierte en INSERTs.
     *
     * @return list<string>
     */
    private function buildInserts(): array
    {
        config(['database.connections.'.self::SEED_CONNECTION => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        $previousDefault = $this->db->getDefaultConnection();
        $inserts = [];

        try {
            Artisan::call('migrate', ['--database' => self::SEED_CONNECTION, '--force' => true]);
            Artisan::call('db:seed', ['--database' => self::SEED_CONNECTION, '--class' => DatabaseSeeder::class, '--force' => true]);

            $connection = $this->db->connection(self::SEED_CONNECTION);

            foreach ([...self::DATA_TABLES, config('database.migrations.table', 'migrations')] as $table) {
                $query = $connection->table($table);
                foreach (self::ORDER_COLUMNS[$table] ?? ['id'] as $column) {
                    $query->orderBy($column);
                }
                $rows = $query->get()->map(fn ($row) => (array) $row)->all();
                if ($rows !== []) {
                    $inserts[] = $this->insertStatement($table, $rows);
                }
            }
        } finally {
            $this->db->setDefaultConnection($previousDefault);
            $this->db->purge(self::SEED_CONNECTION);
        }

        return $inserts;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertStatement(string $table, array $rows): string
    {
        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

        $values = array_map(function (array $row) use ($table) {
            $cells = [];
            foreach ($row as $column => $value) {
                $cells[] = $this->sqlValue($table, $column, $value);
            }

            return '('.implode(', ', $cells).')';
        }, $rows);

        return "INSERT INTO `{$table}` ({$columnList}) VALUES\n".implode(",\n", $values).';';
    }

    private function sqlValue(string $table, string $column, mixed $value): string
    {
        if ($table === 'users' && $column === 'password') {
            return "'".self::ADMIN_PASSWORD_HASH."'";
        }
        if ($table === 'users' && $column === 'remember_token') {
            return 'NULL';
        }
        if (in_array($column, self::TIMESTAMP_COLUMNS, true)) {
            return $value === null ? 'NULL' : 'NOW()';
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".strtr((string) $value, ['\\' => '\\\\', "'" => "\\'", "\n" => '\\n', "\r" => '\\r', "\0" => '\\0'])."'";
    }

    /**
     * Inserta una columna después de la última columna existente (antes de las
     * llaves, índices y restricciones).
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function insertColumn(array $lines, string $column): array
    {
        $position = 0;
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, '`')) {
                $position = $index + 1;
            }
        }

        array_splice($lines, $position, 0, [$column]);

        return $lines;
    }

    /**
     * Divide por comas de primer nivel (ignora comas dentro de paréntesis y comillas).
     *
     * @return list<string>
     */
    private function splitTopLevel(string $definition): array
    {
        $parts = [];
        $depth = 0;
        $quoted = false;
        $current = '';

        for ($i = 0, $length = strlen($definition); $i < $length; $i++) {
            $char = $definition[$i];

            if ($char === "'" && ($i === 0 || $definition[$i - 1] !== '\\')) {
                $quoted = ! $quoted;
            } elseif (! $quoted && $char === '(') {
                $depth++;
            } elseif (! $quoted && $char === ')') {
                $depth--;
            } elseif (! $quoted && $depth === 0 && $char === ',') {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $parts[] = trim($current);

        return $parts;
    }

    /**
     * Orden topológico estable: cada tabla aparece después de las que referencia.
     *
     * @param  array<string, list<string>>  $dependencies
     * @return list<string>
     */
    private function sortByDependencies(array $dependencies): array
    {
        $sorted = [];
        $visiting = [];

        $visit = function (string $table) use (&$visit, &$sorted, &$visiting, $dependencies) {
            if (in_array($table, $sorted, true)) {
                return;
            }
            if (isset($visiting[$table])) {
                throw new RuntimeException("Dependencia circular de llaves foráneas en la tabla {$table}.");
            }
            $visiting[$table] = true;
            foreach ($dependencies[$table] ?? [] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$table]);
            $sorted[] = $table;
        };

        foreach (array_keys($dependencies) as $table) {
            $visit($table);
        }

        return $sorted;
    }
}
