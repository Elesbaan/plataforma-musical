<?php

namespace App\Console\Commands;

use App\Services\ProductionSqlExporter;
use Illuminate\Console\Command;

class ExportProductionSql extends Command
{
    protected $signature = 'db:export-production-sql {--path= : Ruta de salida relativa a la raíz del proyecto}';

    protected $description = 'Regenera database/production_schema.sql desde las migraciones y el DatabaseSeeder';

    public function handle(ProductionSqlExporter $exporter): int
    {
        $path = base_path($this->option('path') ?: ProductionSqlExporter::DEFAULT_PATH);

        $exporter->write($path);

        $this->components->info("Script SQL de producción actualizado: {$path}");

        return self::SUCCESS;
    }
}
