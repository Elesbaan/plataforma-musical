<?php

namespace Tests\Feature;

use App\Services\ProductionSqlExporter;
use Tests\TestCase;

class ProductionSchemaSqlTest extends TestCase
{
    private function committedSql(): string
    {
        $path = base_path(ProductionSqlExporter::DEFAULT_PATH);
        $this->assertFileExists($path, 'Falta database/production_schema.sql');

        return str_replace("\r\n", "\n", file_get_contents($path));
    }

    public function test_script_sql_maestro_esta_sincronizado_con_migraciones_y_seeder(): void
    {
        $expected = app(ProductionSqlExporter::class)->export();

        $this->assertSame(
            $expected,
            $this->committedSql(),
            'database/production_schema.sql está desactualizado. Ejecuta: php artisan db:export-production-sql'
        );
    }

    public function test_script_sql_cumple_reglas_de_importacion(): void
    {
        $sql = $this->committedSql();

        $this->assertStringContainsString("SET FOREIGN_KEY_CHECKS = 0;\n", $sql);
        $this->assertStringEndsWith("SET FOREIGN_KEY_CHECKS = 1;\n", $sql);
        $this->assertStringNotContainsStringIgnoringCase('ALTER TABLE', $sql);

        foreach (['users', 'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'migrations',
            'site_settings', 'categories', 'services', 'service_features', 'service_media',
            'coverage_zones', 'quotes', 'quote_items', 'events', 'event_services'] as $table) {
            $this->assertStringContainsString("CREATE TABLE `{$table}` (", $sql);
        }

        $this->assertSame(
            substr_count($sql, 'CREATE TABLE'),
            substr_count($sql, 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;')
        );

        foreach (['users', 'site_settings', 'coverage_zones', 'categories', 'services', 'service_features', 'migrations'] as $table) {
            $this->assertStringContainsString("INSERT INTO `{$table}`", $sql);
        }
    }

    public function test_tablas_se_crean_despues_de_las_tablas_que_referencian(): void
    {
        $sql = $this->committedSql();

        preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\) ENGINE/s', $sql, $matches, PREG_SET_ORDER);
        $position = array_flip(array_column($matches, 1));

        foreach ($matches as [, $table, $body]) {
            preg_match_all('/REFERENCES `(\w+)`/', $body, $refs);
            foreach ($refs[1] as $referenced) {
                $this->assertLessThan($position[$table], $position[$referenced], "{$table} se crea antes que {$referenced}");
            }
        }
    }
}
