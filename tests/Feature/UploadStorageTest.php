<?php

namespace Tests\Feature;

use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadStorageTest extends TestCase
{
    public function test_disco_uploads_apunta_a_public_uploads_sin_symlinks(): void
    {
        $this->assertSame(public_path('uploads'), config('filesystems.disks.uploads.root'));
        $this->assertSame(storage_path('app/chunks'), config('filesystems.disks.chunks.root'));

        foreach (['branding', 'services/images', 'services/videos'] as $directory) {
            $this->assertFileExists(public_path("uploads/{$directory}/.gitkeep"));
        }
    }

    public function test_url_publica_se_genera_dentro_de_uploads(): void
    {
        $this->assertSame(asset('uploads/branding/logo.png'), UploadService::url('branding/logo.png'));
        $this->assertNull(UploadService::url(null));
    }

    public function test_foto_se_optimiza_a_webp_y_se_reduce_a_1920_px(): void
    {
        Storage::fake(UploadService::DISK);

        $path = app(UploadService::class)->storeOptimizedImage(
            UploadedFile::fake()->image('escenario.jpg', 3000, 1500),
            UploadService::DIR_SERVICE_IMAGES
        );

        $this->assertStringStartsWith('services/images/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk(UploadService::DISK)->assertExists($path);

        [$width, $height] = getimagesizefromstring(Storage::disk(UploadService::DISK)->get($path));
        $this->assertSame(1920, $width);
        $this->assertSame(960, $height);
    }

    public function test_archivo_se_guarda_y_elimina(): void
    {
        Storage::fake(UploadService::DISK);
        $uploads = app(UploadService::class);

        $path = $uploads->store(UploadedFile::fake()->image('logo.png', 400, 120), UploadService::DIR_BRANDING);
        Storage::disk(UploadService::DISK)->assertExists($path);

        $uploads->delete($path);
        Storage::disk(UploadService::DISK)->assertMissing($path);
    }

    public function test_mensajes_de_validacion_en_espanol(): void
    {
        $validator = Validator::make(['brand_name' => ''], ['brand_name' => 'required']);

        $this->assertSame('El nombre comercial es obligatorio.', $validator->errors()->first('brand_name'));
    }
}
