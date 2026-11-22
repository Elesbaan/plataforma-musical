<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceMedia;
use App\Models\User;
use App\Services\ChunkedVideoUploadService;
use App\Services\MediaLimits;
use App\Services\UploadService;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake(UploadService::DISK);
        Storage::fake(ChunkedVideoUploadService::DISK);

        $this->admin = User::factory()->superAdmin()->create();
        $category = Category::create(['name' => 'Show', 'slug' => 'show', 'is_active' => true]);
        $this->service = Service::create([
            'category_id' => $category->id, 'name' => 'Robot LED', 'slug' => 'robot-led',
            'service_type' => 'addon_extra', 'base_price' => 7200, 'price_unit' => 'por presentación',
            'daily_capacity' => 2, 'is_active' => true,
        ]);
    }

    private function mp4Bytes(int $size): string
    {
        $header = "\x00\x00\x00\x20ftypisom\x00\x00\x02\x00isomiso2avc1mp41";

        return $header.str_repeat("\x00", $size - strlen($header));
    }

    /**
     * Envía un archivo completo en fragmentos como lo hace ChunkedVideoUploader.
     *
     * @return list<\Illuminate\Testing\TestResponse>
     */
    private function uploadInChunks(string $bytes, string $fileName = 'show-en-vivo.mp4', ?string $uploadId = null): array
    {
        $chunkBytes = app(MediaLimits::class)->chunkBytes();
        $uploadId ??= (string) Str::uuid();
        $total = (int) ceil(strlen($bytes) / $chunkBytes);
        $responses = [];

        for ($index = 0; $index < $total; $index++) {
            $responses[] = $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [
                'upload_id' => $uploadId,
                'service_id' => $this->service->id,
                'file_name' => $fileName,
                'total_size' => strlen($bytes),
                'total_chunks' => $total,
                'chunk_index' => $index,
                'chunk' => UploadedFile::fake()->createWithContent("{$fileName}.part", substr($bytes, $index * $chunkBytes, $chunkBytes)),
            ]);
        }

        return $responses;
    }

    // ------------------------------------------------------------- Enlaces de video

    public function test_normaliza_enlaces_de_youtube_y_vimeo(): void
    {
        $id = 'dQw4w9WgXcQ';
        foreach ([
            "https://www.youtube.com/watch?v={$id}&t=10s",
            "https://youtu.be/{$id}",
            "https://youtube.com/shorts/{$id}",
            "https://www.youtube.com/embed/{$id}",
            "https://m.youtube.com/watch?v={$id}",
        ] as $url) {
            $this->assertSame("https://www.youtube-nocookie.com/embed/{$id}", VideoEmbed::normalize($url), $url);
        }

        $this->assertSame('https://player.vimeo.com/video/123456789', VideoEmbed::normalize('https://vimeo.com/123456789'));
        $this->assertSame('https://player.vimeo.com/video/123456789?h=abc123', VideoEmbed::normalize('https://vimeo.com/123456789/abc123'));
        $this->assertNull(VideoEmbed::normalize('https://example.com/video.mp4'));
        $this->assertNull(VideoEmbed::normalize('javascript:alert(1)'));
        $this->assertNull(VideoEmbed::normalize('https://www.youtube.com/watch?v=corto'));
    }

    public function test_agrega_video_en_linea(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/servicios/{$this->service->id}/media/video", [
                'embed_url' => 'https://youtu.be/dQw4w9WgXcQ',
                'title' => 'Boda en Metepec',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $media = $this->service->media()->first();
        $this->assertSame(ServiceMedia::TYPE_VIDEO_EMBED, $media->media_type);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $media->url);
        $this->assertFalse($media->is_cover);
    }

    public function test_rechaza_enlace_que_no_es_youtube_ni_vimeo(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/servicios/{$this->service->id}/media/video", ['embed_url' => 'https://ejemplo.com/video'])
            ->assertSessionHasErrors(['embed_url' => 'Pega un enlace válido de YouTube o Vimeo.']);

        $this->assertSame(0, ServiceMedia::count());
    }

    // ------------------------------------------------------------- Fotos

    public function test_sube_fotos_optimizadas_y_la_primera_es_portada(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/servicios/{$this->service->id}/media/fotos", [
                'images' => [
                    UploadedFile::fake()->image('escenario.jpg', 2400, 1600),
                    UploadedFile::fake()->image('pista.png', 800, 600),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Se agregaron 2 fotos.');

        $media = $this->service->media()->get();
        $this->assertCount(2, $media);
        $this->assertTrue($media[0]->is_cover);
        $this->assertFalse($media[1]->is_cover);
        $this->assertSame([1, 2], $media->pluck('sort_order')->all());
        $this->assertSame('escenario', $media[0]->title);

        foreach ($media as $item) {
            $this->assertStringStartsWith('services/images/', $item->file_path);
            $this->assertStringEndsWith('.webp', $item->file_path);
            Storage::disk(UploadService::DISK)->assertExists($item->file_path);
            $this->assertSame(asset('uploads/'.$item->file_path), $item->url);
        }
    }

    public function test_rechaza_fotos_invalidas(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/servicios/{$this->service->id}/media/fotos", [
                'images' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHasErrors(['images.0' => 'Las fotos deben ser JPG, PNG o WebP.']);

        $this->assertSame(0, ServiceMedia::count());
    }

    public function test_galeria_se_entrega_con_limites_de_subida(): void
    {
        $this->actingAs($this->admin)
            ->get("/admin/servicios/{$this->service->id}/editar")
            ->assertInertia(fn (Assert $page) => $page
                ->where('mediaLimits.chunkBytes', app(MediaLimits::class)->chunkBytes())
                ->where('mediaLimits.videoExtensions', ['mp4', 'webm', 'mov'])
                ->has('mediaLimits.maxVideoBytes'));
    }

    // ------------------------------------------------------------- Portada, orden y borrado

    public function test_cambia_portada_y_reordena(): void
    {
        $this->actingAs($this->admin)->post("/admin/servicios/{$this->service->id}/media/fotos", [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ]);
        [$a, $b, $c] = $this->service->media()->get()->all();

        $this->actingAs($this->admin)->patch("/admin/media/{$c->id}", ['is_cover' => true])->assertSessionHasNoErrors();
        $this->assertFalse($a->fresh()->is_cover);
        $this->assertTrue($c->fresh()->is_cover);

        $this->actingAs($this->admin)->patch("/admin/media/{$c->id}", ['move' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(['a', 'c', 'b'], $this->service->media()->pluck('title')->all());

        $this->actingAs($this->admin)->patch("/admin/media/{$a->id}", ['move' => 'up']);
        $this->assertSame(['a', 'c', 'b'], $this->service->media()->pluck('title')->all());
    }

    public function test_eliminar_portada_borra_archivo_y_asigna_nueva_portada(): void
    {
        $this->actingAs($this->admin)->post("/admin/servicios/{$this->service->id}/media/fotos", [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);
        [$a, $b] = $this->service->media()->get()->all();

        $this->actingAs($this->admin)->delete("/admin/media/{$a->id}")->assertSessionHas('success');

        $this->assertModelMissing($a);
        Storage::disk(UploadService::DISK)->assertMissing($a->file_path);
        $this->assertTrue($b->fresh()->is_cover);
        $this->assertSame(1, $b->fresh()->sort_order);
    }

    public function test_invitado_no_gestiona_multimedia(): void
    {
        $this->post("/admin/servicios/{$this->service->id}/media/fotos", [])->assertRedirect('/admin/login');
        $this->postJson('/admin/media/upload-chunk', [])->assertUnauthorized();
    }

    // ------------------------------------------------------------- Video por bloques

    public function test_sube_video_por_bloques_y_lo_ensambla_en_public_uploads(): void
    {
        $chunkBytes = app(MediaLimits::class)->chunkBytes();
        $bytes = $this->mp4Bytes($chunkBytes * 2 + 1234);

        $responses = $this->uploadInChunks($bytes);

        $this->assertCount(3, $responses);
        $responses[0]->assertOk()->assertJson(['status' => 'receiving', 'received' => 1, 'total' => 3]);
        $responses[1]->assertOk()->assertJson(['status' => 'receiving', 'received' => 2, 'total' => 3]);
        $responses[2]->assertCreated()->assertJson(['status' => 'complete', 'media' => ['media_type' => 'video_local', 'title' => 'show-en-vivo']]);

        $media = $this->service->media()->first();
        $this->assertStringStartsWith('services/videos/', $media->file_path);
        $this->assertStringEndsWith('.mp4', $media->file_path);
        $this->assertSame($bytes, Storage::disk(UploadService::DISK)->get($media->file_path));

        // Los fragmentos temporales se eliminan al ensamblar.
        $this->assertSame([], Storage::disk(ChunkedVideoUploadService::DISK)->directories());
    }

    public function test_rechaza_archivo_que_no_es_video_y_limpia_temporales(): void
    {
        $responses = $this->uploadInChunks(str_repeat('A', 1000), 'falso.mp4');

        $responses[0]->assertUnprocessable()->assertJsonValidationErrors(['file_name' => 'El archivo no es un video válido. Usa formatos MP4, WebM o MOV.']);
        $this->assertSame(0, ServiceMedia::count());
        $this->assertSame([], Storage::disk(UploadService::DISK)->allFiles('services/videos'));
        $this->assertSame([], Storage::disk(ChunkedVideoUploadService::DISK)->directories());
    }

    public function test_rechaza_extension_identificador_y_conteo_invalidos(): void
    {
        $base = [
            'upload_id' => (string) Str::uuid(),
            'service_id' => $this->service->id,
            'file_name' => 'video.mp4',
            'total_size' => 1000,
            'total_chunks' => 1,
            'chunk_index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('video.part', $this->mp4Bytes(1000)),
        ];

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [...$base, 'file_name' => 'malicioso.php'])
            ->assertJsonValidationErrors(['file_name' => 'Formato no permitido. Sube videos MP4, WebM o MOV.']);

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [...$base, 'upload_id' => '../../config'])
            ->assertJsonValidationErrors('upload_id');

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [...$base, 'total_chunks' => 50])
            ->assertJsonValidationErrors('total_chunks');

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [...$base, 'total_size' => app(MediaLimits::class)->maxVideoBytes() + 1])
            ->assertJsonValidationErrors('total_size');

        $this->assertSame(0, ServiceMedia::count());
    }

    public function test_fragmentos_de_otro_archivo_se_rechazan(): void
    {
        $chunkBytes = app(MediaLimits::class)->chunkBytes();
        $uploadId = (string) Str::uuid();
        $bytes = $this->mp4Bytes($chunkBytes + 10);

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [
            'upload_id' => $uploadId, 'service_id' => $this->service->id, 'file_name' => 'a.mp4',
            'total_size' => strlen($bytes), 'total_chunks' => 2, 'chunk_index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('a.part', substr($bytes, 0, $chunkBytes)),
        ])->assertOk();

        $this->actingAs($this->admin)->postJson('/admin/media/upload-chunk', [
            'upload_id' => $uploadId, 'service_id' => $this->service->id, 'file_name' => 'otro.mp4',
            'total_size' => strlen($bytes), 'total_chunks' => 2, 'chunk_index' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('b.part', substr($bytes, $chunkBytes)),
        ])->assertJsonValidationErrors('upload_id');

        $this->assertSame(0, ServiceMedia::count());
    }

    public function test_tamano_de_fragmento_respeta_configuracion_y_limites_de_php(): void
    {
        config(['media.chunk_kb' => 2048]);
        $bytes = app(MediaLimits::class)->chunkBytes();

        $this->assertLessThanOrEqual(2 * 1024 * 1024, $bytes);
        $this->assertGreaterThanOrEqual(256 * 1024, $bytes);
    }

    public function test_comando_limpia_fragmentos_abandonados(): void
    {
        $disk = Storage::disk(ChunkedVideoUploadService::DISK);
        $disk->put('abandonada/00000.part', 'x');
        $disk->put('reciente/00000.part', 'x');
        touch($disk->path('abandonada/00000.part'), now()->subDays(2)->getTimestamp());

        $this->artisan('media:clean-chunks')->assertSuccessful();

        $this->assertSame(['reciente'], $disk->directories());
    }
}
