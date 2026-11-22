<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Único punto de acceso a los archivos públicos subidos desde el panel.
 * Se guardan en public/uploads/... (disco `uploads`) para no depender de
 * symlinks (`storage:link`), que no siempre están disponibles en hosting compartido.
 */
class UploadService
{
    public const DISK = 'uploads';

    public const DIR_BRANDING = 'branding';

    public const DIR_SERVICE_IMAGES = 'services/images';

    public const DIR_SERVICE_VIDEOS = 'services/videos';

    /** Ancho máximo de las fotos optimizadas. */
    private const MAX_IMAGE_WIDTH = 1920;

    private const WEBP_QUALITY = 82;

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * URL pública de un archivo subido. Usa asset() para funcionar también
     * cuando el sitio vive en una subcarpeta.
     */
    public static function url(?string $path): ?string
    {
        return $path ? asset('uploads/'.ltrim($path, '/')) : null;
    }

    /**
     * Guarda un archivo tal cual (logos, favicon) con nombre aleatorio.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());

        return $this->disk()->putFileAs($directory, $file, Str::uuid().'.'.$extension);
    }

    /**
     * Guarda una foto optimizada: reduce a 1920 px de ancho como máximo y la
     * convierte a WebP. Si GD no está disponible, guarda el archivo original.
     */
    public function storeOptimizedImage(UploadedFile $file, string $directory): string
    {
        $image = $this->loadImage($file);

        if ($image === null || ! function_exists('imagewebp')) {
            return $this->store($file, $directory);
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_IMAGE_WIDTH) {
            $newHeight = (int) round($height * self::MAX_IMAGE_WIDTH / $width);
            $resized = imagecreatetruecolor(self::MAX_IMAGE_WIDTH, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, self::MAX_IMAGE_WIDTH, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagewebp($image, null, self::WEBP_QUALITY);
        $contents = ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        $this->disk()->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }

    /**
     * Ruta absoluta dentro del disco (para ensamblar videos).
     */
    public function absolutePath(string $path): string
    {
        return $this->disk()->path($path);
    }

    private function loadImage(UploadedFile $file): ?\GdImage
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($image === false) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }
}
