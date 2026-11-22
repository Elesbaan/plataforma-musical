<?php

namespace App\Console\Commands;

use App\Services\ChunkedVideoUploadService;
use Illuminate\Console\Command;

class CleanStaleChunks extends Command
{
    protected $signature = 'media:clean-chunks {--hours= : Antigüedad mínima en horas (por defecto config media.stale_chunk_hours)}';

    protected $description = 'Elimina fragmentos de subidas de video abandonadas';

    public function handle(ChunkedVideoUploadService $uploader): int
    {
        $hours = (int) ($this->option('hours') ?: config('media.stale_chunk_hours'));
        $removed = $uploader->cleanStale($hours);

        $this->components->info("Subidas abandonadas eliminadas: {$removed}.");

        return self::SUCCESS;
    }
}
