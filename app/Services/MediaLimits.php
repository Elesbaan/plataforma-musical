<?php

namespace App\Services;

/**
 * Límites de subida multimedia calculados a partir de config/media.php y de
 * los límites reales de PHP del servidor (upload_max_filesize, post_max_size).
 */
class MediaLimits
{
    /** Margen reservado en cada petición para los demás campos del formulario. */
    private const POST_OVERHEAD_BYTES = 256 * 1024;

    private const MIN_CHUNK_BYTES = 256 * 1024;

    /**
     * Tamaño de cada fragmento: el objetivo (2 MB por defecto) o menos si el
     * servidor no admite peticiones de ese tamaño.
     */
    public function chunkBytes(): int
    {
        $target = config('media.chunk_kb') * 1024;
        $limits = array_filter([
            $this->iniBytes(ini_get('upload_max_filesize')),
            ($post = $this->iniBytes(ini_get('post_max_size'))) ? $post - self::POST_OVERHEAD_BYTES : null,
        ], fn ($value) => $value !== null && $value > 0);

        return max(self::MIN_CHUNK_BYTES, min($target, ...($limits ?: [$target])));
    }

    public function maxVideoBytes(): int
    {
        return config('media.max_video_mb') * 1024 * 1024;
    }

    /**
     * @return list<string>
     */
    public function videoExtensions(): array
    {
        return config('media.video_extensions');
    }

    /**
     * Datos que necesita el frontend para validar antes de subir.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chunkBytes' => $this->chunkBytes(),
            'maxVideoBytes' => $this->maxVideoBytes(),
            'videoExtensions' => $this->videoExtensions(),
            'maxImageKb' => config('media.max_image_kb'),
            'maxImagesPerUpload' => config('media.max_images_per_upload'),
        ];
    }

    /**
     * Convierte valores de php.ini ("2M", "512K", "1G") a bytes. Null si no hay límite.
     */
    private function iniBytes(string|false $value): ?int
    {
        if ($value === false || trim($value) === '' || (int) $value <= 0) {
            return null;
        }

        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
