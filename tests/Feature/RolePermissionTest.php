<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceMedia;
use App\Models\User;
use App\Services\ChunkedVideoUploadService;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Proveedor Aliado (catalog.manage_own): solo sus servicios vinculados y sin
 * tocar datos comerciales.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    private Service $own;

    private Service $foreign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake(UploadService::DISK);
        Storage::fake(ChunkedVideoUploadService::DISK);

        $category = Category::create(['name' => 'Regional', 'slug' => 'regional', 'is_active' => true]);
        $this->own = $this->service($category, 'Mariachi Tradicional', 4800);
        $this->foreign = $this->service($category, 'Banda Sinaloense', 9000);

        $this->partner = User::factory()->withRole(Role::PARTNER)->create();
        $this->partner->services()->attach($this->own);
    }

    private function service(Category $category, string $name, int $price): Service
    {
        $service = Service::create([
            'category_id' => $category->id, 'name' => $name, 'slug' => Str::slug($name),
            'service_type' => 'partner_act', 'base_price' => $price, 'price_unit' => 'por presentación',
            'daily_capacity' => 2, 'is_active' => true,
        ]);
        $service->features()->create(['feature_text' => '12 piezas', 'sort_order' => 1]);

        return $service;
    }

    private function contentPayload(array $overrides = []): array
    {
        return [
            'short_description' => 'Serenata con 12 elementos',
            'full_description' => 'Mariachi completo con trajes de gala.',
            'daily_capacity' => 3,
            'features' => ['12 piezas', 'Trajes de gala'],
            ...$overrides,
        ];
    }

    // ------------------------------------------------------------- Módulos prohibidos

    public function test_proveedor_no_accede_a_identidad_ni_otros_modulos(): void
    {
        $this->actingAs($this->partner);

        $this->get('/admin')->assertOk();
        $this->get('/admin/identidad')->assertForbidden();
        $this->put('/admin/identidad', ['brand_name' => 'Hackeado'])->assertForbidden();
        $this->get('/admin/zonas')->assertForbidden();
        $this->get('/admin/categorias')->assertForbidden();
        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/perfiles')->assertForbidden();
        $this->get('/admin/servicios/crear')->assertForbidden();
        $this->post('/admin/servicios', ['name' => 'Nuevo'])->assertForbidden();
    }

    public function test_menu_recibe_solo_los_permisos_del_proveedor(): void
    {
        $this->actingAs($this->partner)->get('/admin')
            ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', fn ($keys) => collect($keys)->sort()->values()->all() === ['catalog.manage_own', 'events.view_own']));
    }

    // ------------------------------------------------------------- Servicios ajenos

    public function test_solo_lista_sus_servicios_vinculados(): void
    {
        $this->actingAs($this->partner)->get('/admin/servicios')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('services.data', 1)
                ->where('services.data.0.id', $this->own->id)
                ->where('abilities.create', false));
    }

    public function test_no_puede_editar_servicios_ajenos(): void
    {
        $this->actingAs($this->partner);

        $this->get("/admin/servicios/{$this->foreign->id}/editar")->assertForbidden();
        $this->put("/admin/servicios/{$this->foreign->id}", $this->contentPayload())->assertForbidden();
        $this->delete("/admin/servicios/{$this->foreign->id}")->assertForbidden();
        $this->post("/admin/servicios/{$this->foreign->id}/media/fotos", ['images' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
        $this->post("/admin/servicios/{$this->foreign->id}/media/video", ['embed_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertForbidden();

        $this->assertSame('9000.00', $this->foreign->fresh()->base_price);
        $this->assertSame(0, $this->foreign->media()->count());
    }

    public function test_no_puede_modificar_ni_borrar_archivos_de_servicios_ajenos(): void
    {
        $media = $this->foreign->media()->create(['media_type' => ServiceMedia::TYPE_VIDEO_EMBED, 'embed_url' => 'https://player.vimeo.com/video/123456789']);

        $this->actingAs($this->partner)->patch("/admin/media/{$media->id}", ['move' => 'down'])->assertForbidden();
        $this->actingAs($this->partner)->delete("/admin/media/{$media->id}")->assertForbidden();

        $this->assertModelExists($media);
    }

    public function test_no_puede_subir_video_por_bloques_a_un_servicio_ajeno(): void
    {
        $this->actingAs($this->partner)->postJson('/admin/media/upload-chunk', [
            'upload_id' => (string) Str::uuid(),
            'service_id' => $this->foreign->id,
            'file_name' => 'video.mp4',
            'total_size' => 100,
            'total_chunks' => 1,
            'chunk_index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('v.part', str_repeat('x', 100)),
        ])->assertForbidden();

        $this->assertSame([], Storage::disk(ChunkedVideoUploadService::DISK)->allFiles());
    }

    // ------------------------------------------------------------- Su propio servicio

    public function test_edita_descripcion_capacidad_y_caracteristicas_de_su_servicio(): void
    {
        $this->actingAs($this->partner)->get("/admin/servicios/{$this->own->id}/editar")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('abilities.managePricing', false)
                ->where('abilities.delete', false));

        $this->actingAs($this->partner)
            ->put("/admin/servicios/{$this->own->id}", $this->contentPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $service = $this->own->fresh();
        $this->assertSame('Serenata con 12 elementos', $service->short_description);
        $this->assertSame(3, $service->daily_capacity);
        $this->assertSame(['12 piezas', 'Trajes de gala'], $service->features->pluck('feature_text')->all());
        $this->assertSame('4800.00', $service->base_price);
    }

    public function test_no_puede_alterar_el_precio_ni_datos_comerciales_de_su_servicio(): void
    {
        $this->actingAs($this->partner)
            ->put("/admin/servicios/{$this->own->id}", $this->contentPayload([
                'base_price' => 1,
                'extra_hour_price' => 10,
                'name' => 'Mariachi Premium',
                'is_featured' => true,
            ]))
            ->assertSessionHasErrors([
                'base_price' => 'No tienes permiso para modificar el precio de este servicio.',
                'extra_hour_price',
                'name',
                'is_featured',
            ]);

        $service = $this->own->fresh();
        $this->assertSame('4800.00', $service->base_price);
        $this->assertSame('Mariachi Tradicional', $service->name);
        $this->assertFalse($service->is_featured);
        $this->assertSame(2, $service->daily_capacity);
    }

    public function test_no_puede_eliminar_su_propio_servicio(): void
    {
        $this->actingAs($this->partner)->delete("/admin/servicios/{$this->own->id}")->assertForbidden();

        $this->assertModelExists($this->own);
    }

    public function test_gestiona_la_galeria_de_su_servicio(): void
    {
        $this->actingAs($this->partner)
            ->post("/admin/servicios/{$this->own->id}/media/fotos", ['images' => [UploadedFile::fake()->image('serenata.jpg')]])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->partner)
            ->post("/admin/servicios/{$this->own->id}/media/video", ['embed_url' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->own->media()->count());
        $photo = $this->own->media()->where('media_type', ServiceMedia::TYPE_IMAGE)->first();
        $this->assertTrue($photo->is_cover);

        $this->actingAs($this->partner)->patch("/admin/media/{$photo->id}", ['move' => 'down'])->assertSessionHasNoErrors();
        $this->actingAs($this->partner)->delete("/admin/media/{$photo->id}")->assertSessionHas('success');

        $this->assertModelMissing($photo);
        Storage::disk(UploadService::DISK)->assertMissing($photo->file_path);
    }

    public function test_sube_video_por_bloques_a_su_servicio(): void
    {
        $bytes = "\x00\x00\x00\x20ftypisom\x00\x00\x02\x00isomiso2avc1mp41".str_repeat("\x00", 2000);

        $this->actingAs($this->partner)->postJson('/admin/media/upload-chunk', [
            'upload_id' => (string) Str::uuid(),
            'service_id' => $this->own->id,
            'file_name' => 'serenata.mp4',
            'total_size' => strlen($bytes),
            'total_chunks' => 1,
            'chunk_index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('s.part', $bytes),
        ])->assertCreated();

        $this->assertSame(1, $this->own->media()->where('media_type', ServiceMedia::TYPE_VIDEO_LOCAL)->count());
    }

    public function test_pierde_acceso_al_desvincular_el_servicio(): void
    {
        $this->partner->services()->detach($this->own);

        $this->actingAs($this->partner)->get("/admin/servicios/{$this->own->id}/editar")->assertForbidden();
    }

    // ------------------------------------------------------------- Otros perfiles

    public function test_coordinador_de_ventas_no_accede_al_catalogo(): void
    {
        $this->actingAs(User::factory()->withRole(Role::SALES_COORDINATOR)->create())
            ->get('/admin/servicios')
            ->assertForbidden();
    }

    public function test_quien_tiene_manage_all_si_cambia_precios(): void
    {
        $role = Role::create(['name' => 'Catálogo', 'slug' => 'catalogo']);
        $role->permissions()->attach(\App\Models\Permission::where('key', 'catalog.manage_all')->value('id'));
        $editor = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($editor)->put("/admin/servicios/{$this->foreign->id}", [
            ...$this->contentPayload(),
            'category_id' => $this->foreign->category_id,
            'name' => 'Banda Sinaloense',
            'service_type' => 'partner_act',
            'base_price' => 9500,
            'price_unit' => 'por presentación',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('9500.00', $this->foreign->fresh()->base_price);
    }

    // ------------------------------------------------------------- Agenda (events.view_own)

    private function eventWith(array $services, string $date, array $overrides = []): \App\Models\Event
    {
        $event = \App\Models\Event::create([
            'title' => 'Boda — Hacienda', 'event_date' => $date, 'start_time' => '18:00', 'city_name' => 'Metepec',
            'venue_name' => 'Hacienda', 'client_name' => 'Cliente Privado', 'client_phone' => '7220000000',
            'agreed_total' => 50000, 'deposit_paid' => 20000, 'status' => 'reserved', 'internal_notes' => 'Nota interna',
            ...$overrides,
        ]);
        foreach ($services as $service) {
            $event->services()->attach($service->id, ['quantity' => 1]);
        }

        return $event;
    }

    public function test_proveedor_solo_ve_eventos_con_sus_servicios_sin_datos_financieros(): void
    {
        $date = now()->addDays(10)->toDateString();
        $shared = $this->eventWith([$this->own, $this->foreign], $date);
        $this->eventWith([$this->foreign], $date, ['title' => 'Evento ajeno']);

        $this->actingAs($this->partner)->get('/admin/agenda?mes='.substr($date, 0, 7))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('abilities.manage', false)
                ->where('abilities.financials', false)
                ->has('events', 1)
                ->where('events.0.id', $shared->id)
                ->where('events.0.is_full_view', false)
                ->has('events.0.services', 1)
                ->where('events.0.services.0.id', $this->own->id)
                ->missing('events.0.agreed_total')
                ->missing('events.0.deposit_paid')
                ->missing('events.0.balance')
                ->missing('events.0.client_name')
                ->missing('events.0.client_phone')
                ->missing('events.0.internal_notes')
                ->has('upcoming', 1));
    }

    public function test_proveedor_ve_el_detalle_operativo_de_su_evento_pero_no_el_ajeno(): void
    {
        $date = now()->addDays(10)->toDateString();
        $mine = $this->eventWith([$this->own, $this->foreign], $date);
        $foreignEvent = $this->eventWith([$this->foreign], $date);

        $this->actingAs($this->partner)->get("/admin/agenda/{$mine->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.city_name', 'Metepec')
                ->where('event.start_time', '18:00')
                ->where('abilities.manage', false)
                ->has('event.services', 1)
                ->missing('event.agreed_total')
                ->missing('event.client_name'));

        $this->actingAs($this->partner)->get("/admin/agenda/{$foreignEvent->id}")->assertForbidden();
    }

    public function test_alertas_de_capacidad_del_proveedor_solo_de_sus_servicios(): void
    {
        $date = now()->addDays(10)->toDateString();
        $this->foreign->update(['daily_capacity' => 1]);
        $this->eventWith([$this->foreign], $date);

        $this->actingAs($this->partner)->get('/admin/agenda?mes='.substr($date, 0, 7))
            ->assertInertia(fn (Assert $page) => $page->where('saturatedDays', []));
    }

    public function test_proveedor_no_accede_a_cotizaciones(): void
    {
        $this->actingAs($this->partner)->get('/admin/cotizaciones')->assertForbidden();
    }
}
