<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceMedia;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Recibe videos en fragmentos para esquivar los límites de subida del hosting
 * compartido. Los fragmentos se guardan en storage/app/chunks/{uploadId} y,
 * al recibir el último, se ensamblan en public/uploads/services/videos.
 */
class ChunkedVideoUploadService
{
    public const DISK = 'chunks';

    private const META_FILE = 'meta.json';

    public function __construct(
        private readonly UploadService $uploads,
        private readonly ServiceMediaService $media,
        private readonly MediaLimits $limits,
    ) {}

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * Guarda un fragmento y, si era el último, ensambla el video.
     *
     * @param  array{upload_id: string, chunk_index: int, total_chunks: int, total_size: int, file_name: string, service_id: int}  $data
     * @return array{status: 'receiving', received: int, total: int}|array{status: 'complete', media: ServiceMedia}
     *
     * @throws ValidationException
     */
    public function receive(array $data, UploadedFile $chunk, int $userId): array
    {
        $uploadId = $data['upload_id'];
        $this->guardMetadata($uploadId, [
            'service_id' => (int) $data['service_id'],
            'user_id' => $userId,
            'file_name' => $data['file_name'],
            'total_chunks' => (int) $data['total_chunks'],
            'total_size' => (int) $data['total_size'],
        ]);

        $this->disk()->putFileAs($uploadId, $chunk, $this->partName((int) $data['chunk_index']));

        $received = $this->receivedCount($uploadId);
        $total = (int) $data['total_chunks'];

        if ($received < $total) {
            return ['status' => 'receiving', 'received' => $received, 'total' => $total];
        }

        $media = $this->assemble($uploadId, $data);

        return ['status' => 'complete', 'media' => $media];
    }

    /**
     * Elimina fragmentos de subidas abandonadas.
     *
     * @return int Cantidad de subidas eliminadas.
     */
    public function cleanStale(int $olderThanHours): int
    {
        $limit = now()->subHours($olderThanHours)->getTimestamp();
        $removed = 0;

        foreach ($this->disk()->directories() as $directory) {
            $files = $this->disk()->files($directory);
            $lastModified = $files
                ? max(array_map(fn ($file) => $this->disk()->lastModified($file), $files))
                : 0;

            if ($lastModified < $limit) {
                $this->disk()->deleteDirectory($directory);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Todos los fragmentos de una subida deben describir el mismo archivo.
     *
     * @param  array<string, mixed>  $meta
     */
    private function guardMetadata(string $uploadId, array $meta): void
    {
        $path = "{$uploadId}/".self::META_FILE;

        if (! $this->disk()->exists($path)) {
            $this->disk()->put($path, json_encode($meta));

            return;
        }

        if (json_decode($this->disk()->get($path), true) !== $meta) {
            throw ValidationException::withMessages([
                'upload_id' => 'Los fragmentos no corresponden al mismo video. Vuelve a iniciar la subida.',
            ]);
        }
    }

    private function receivedCount(string $uploadId): int
    {
        return count(array_filter(
            $this->disk()->files($uploadId),
            fn ($file) => str_ends_with($file, '.part')
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function assemble(string $uploadId, array $data): ServiceMedia
    {
        $extension = strtolower(pathinfo($data['file_name'], PATHINFO_EXTENSION));
        $relative = UploadService::DIR_SERVICE_VIDEOS.'/'.Str::uuid().'.'.$extension;
        $absolute = $this->uploads->absolutePath($relative);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        try {
            $output = fopen($absolute, 'wb');

            for ($index = 0; $index < (int) $data['total_chunks']; $index++) {
                $part = "{$uploadId}/{$this->partName($index)}";
                if (! $this->disk()->exists($part)) {
                    throw ValidationException::withMessages(['chunk' => 'Falta un fragmento del video. Vuelve a intentarlo.']);
                }
                $input = $this->disk()->readStream($part);
                stream_copy_to_stream($input, $output);
                fclose($input);
            }

            fclose($output);
            $output = null;

            $this->verifyAssembledFile($absolute, (int) $data['total_size']);
        } catch (\Throwable $e) {
            if (isset($output) && is_resource($output)) {
                fclose($output);
            }
            @unlink($absolute);
            throw $e;
        } finally {
            $this->disk()->deleteDirectory($uploadId);
        }

        $service = Service::findOrFail($data['service_id']);
        $title = Str::limit(pathinfo($data['file_name'], PATHINFO_FILENAME), 250, '');

        return $this->media->addLocalVideo($service, $relative, $title);
    }

    /**
     * @throws ValidationException
     */
    private function verifyAssembledFile(string $absolute, int $expectedSize): void
    {
        clearstatcache(true, $absolute);

        if (filesize($absolute) !== $expectedSize) {
            throw ValidationException::withMessages([
                'chunk' => 'El video llegó incompleto. Revisa tu conexión y vuelve a intentarlo.',
            ]);
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);

        if (! str_starts_with($mime, 'video/')) {
            throw ValidationException::withMessages([
                'file_name' => 'El archivo no es un video válido. Usa formatos MP4, WebM o MOV.',
            ]);
        }

        if ($expectedSize > $this->limits->maxVideoBytes()) {
            throw ValidationException::withMessages(['total_size' => 'El video supera el tamaño máximo permitido.']);
        }
    }

    private function partName(int $index): string
    {
        return sprintf('%05d.part', $index);
    }
}
