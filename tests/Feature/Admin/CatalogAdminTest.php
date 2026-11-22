<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Event;
use App\Models\Service;
use App\Models\ServiceMedia;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->superAdmin()->create();
    }

    private function category(array $attributes = []): Category
    {
        static $n = 0;
        $n++;

        return Category::create(['name' => "Categoría {$n}", 'slug' => "categoria-{$n}", 'is_active' => true, ...$attributes]);
    }

    private function service(Category $category, array $attributes = []): Service
    {
        static $n = 0;
        $n++;

        return Service::create([
            'category_id' => $category->id, 'name' => "Servicio {$n}", 'slug' => "servicio-{$n}",
            'service_type' => 'core_band', 'base_price' => 10000, 'price_unit' => 'por evento',
            'daily_capacity' => 1, 'is_active' => true, ...$attributes,
        ]);
    }

    private function servicePayload(Category $category, array $overrides = []): array
    {
        return [
            'category_id' => $category->id,
            'name' => 'Grupo Versátil Premium',
            'short_description' => 'Show completo',
            'full_description' => 'Descripción larga',
            'service_type' => 'core_band',
            'base_price' => '28500',
            'price_unit' => 'por evento (5 hrs)',
            'daily_capacity' => '2',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
            'features' => ['Sonido profesional', '  ', 'Iluminación básica'],
            ...$overrides,
        ];
    }

    // ---------------------------------------------------------------- Categorías

    public function test_invitado_no_accede_al_catalogo(): void
    {
        $this->get('/admin/categorias')->assertRedirect('/admin/login');
        $this->get('/admin/servicios')->assertRedirect('/admin/login');
        $this->post('/admin/servicios', [])->assertRedirect('/admin/login');
    }

    public function test_crea_categoria_con_slug_unico(): void
    {
        $this->category(['name' => 'Solistas', 'slug' => 'solistas']);

        $this->actingAs($this->admin)
            ->post('/admin/categorias', ['name' => 'Solistas', 'icon' => 'MicVocal', 'is_active' => true])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['slug' => 'solistas-2', 'icon' => 'MicVocal']);
    }

    public function test_editar_categoria_conserva_el_slug(): void
    {
        $category = $this->category(['name' => 'Original', 'slug' => 'original']);

        $this->actingAs($this->admin)
            ->put("/admin/categorias/{$category->id}", ['name' => 'Renombrada', 'is_active' => false])
            ->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('Renombrada', $category->name);
        $this->assertSame('original', $category->slug);
        $this->assertFalse($category->is_active);
    }

    public function test_icono_invalido_se_rechaza(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/categorias', ['name' => 'X', 'icon' => '<script>', 'is_active' => true])
            ->assertSessionHasErrors('icon');
    }

    public function test_no_elimina_categoria_con_servicios(): void
    {
        $category = $this->category();
        $this->service($category);

        $this->actingAs($this->admin)->from('/admin/categorias')
            ->delete("/admin/categorias/{$category->id}")
            ->assertRedirect('/admin/categorias')
            ->assertSessionHas('error');

        $this->assertModelExists($category);
        $this->assertSame(1, Service::count());
    }

    public function test_elimina_categoria_vacia(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)->delete("/admin/categorias/{$category->id}")->assertSessionHas('success');

        $this->assertModelMissing($category);
    }

    public function test_lista_categorias_con_conteo_de_servicios(): void
    {
        $category = $this->category();
        $this->service($category);

        $this->actingAs($this->admin)->get('/admin/categorias')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Categories/Index')
                ->where('categories.0.services_count', 1));
    }

    // ---------------------------------------------------------------- Servicios

    public function test_lista_servicios_con_filtros(): void
    {
        $bands = $this->category();
        $extras = $this->category();
        $this->service($bands, ['name' => 'Grupo Versátil']);
        $this->service($extras, ['name' => 'Robot LED', 'is_active' => false]);

        $this->actingAs($this->admin)->get('/admin/servicios?search=robot')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Services/Index')
                ->has('services.data', 1)
                ->where('services.data.0.name', 'Robot LED'));

        $this->actingAs($this->admin)->get("/admin/servicios?category={$bands->id}&status=active")
            ->assertInertia(fn (Assert $page) => $page->has('services.data', 1)->where('services.data.0.name', 'Grupo Versátil'));
    }

    public function test_formularios_de_alta_y_edicion_cargan(): void
    {
        $service = $this->service($this->category());

        $this->actingAs($this->admin)->get('/admin/servicios/crear')
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Services/Create')->has('categories', 1));

        $this->actingAs($this->admin)->get("/admin/servicios/{$service->id}/editar")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Services/Edit')
                ->where('service.id', $service->id)
                ->has('service.features')
                ->has('service.media'));
    }

    public function test_crea_servicio_con_caracteristicas_y_capacidad_diaria(): void
    {
        $category = $this->category();

        $response = $this->actingAs($this->admin)->post('/admin/servicios', $this->servicePayload($category));

        $service = Service::firstWhere('name', 'Grupo Versátil Premium');
        $response->assertRedirect("/admin/servicios/{$service->id}/editar")->assertSessionHas('success');

        $this->assertSame('grupo-versatil-premium', $service->slug);
        $this->assertSame(2, $service->daily_capacity);
        $this->assertSame('28500.00', $service->base_price);
        $this->assertTrue($service->is_featured);
        $this->assertSame(['Sonido profesional', 'Iluminación básica'], $service->features->pluck('feature_text')->all());
        $this->assertSame([1, 2], $service->features->pluck('sort_order')->all());
    }

    public function test_actualiza_servicio_y_reemplaza_caracteristicas(): void
    {
        $category = $this->category();
        $service = $this->service($category);
        $service->features()->create(['feature_text' => 'Vieja', 'sort_order' => 1]);
        $originalSlug = $service->slug;

        $this->actingAs($this->admin)
            ->put("/admin/servicios/{$service->id}", $this->servicePayload($category, [
                'name' => 'Nombre nuevo',
                'daily_capacity' => '3',
                'features' => ['Nueva B', 'Nueva A'],
            ]))
            ->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame('Nombre nuevo', $service->name);
        $this->assertSame($originalSlug, $service->slug);
        $this->assertSame(3, $service->daily_capacity);
        $this->assertSame(['Nueva B', 'Nueva A'], $service->features->pluck('feature_text')->all());
    }

    public function test_validaciones_de_servicio(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)
            ->post('/admin/servicios', $this->servicePayload($category, [
                'name' => '',
                'category_id' => 9999,
                'service_type' => 'otro',
                'base_price' => '-5',
                'daily_capacity' => '0',
            ]))
            ->assertSessionHasErrors([
                'name' => 'El nombre es un dato obligatorio.',
                'category_id',
                'service_type',
                'base_price',
                'daily_capacity' => 'La capacidad diaria debe ser de al menos 1 evento por fecha.',
            ]);

        $this->assertSame(0, Service::count());
    }

    public function test_elimina_servicio_y_sus_archivos(): void
    {
        Storage::fake(UploadService::DISK);
        Storage::disk(UploadService::DISK)->put('services/images/foto.webp', 'x');

        $service = $this->service($this->category());
        $service->media()->create(['media_type' => ServiceMedia::TYPE_IMAGE, 'file_path' => 'services/images/foto.webp']);

        $this->actingAs($this->admin)
            ->delete("/admin/servicios/{$service->id}")
            ->assertRedirect('/admin/servicios')
            ->assertSessionHas('success');

        $this->assertModelMissing($service);
        $this->assertSame(0, ServiceMedia::count());
        Storage::disk(UploadService::DISK)->assertMissing('services/images/foto.webp');
    }

    public function test_no_elimina_servicio_asignado_a_eventos(): void
    {
        $service = $this->service($this->category());
        $event = Event::create([
            'title' => 'Boda', 'event_date' => '2026-12-12', 'city_name' => 'Toluca',
            'client_name' => 'Ana', 'agreed_total' => 10000,
        ]);
        $event->services()->attach($service->id, ['quantity' => 1]);

        $this->actingAs($this->admin)->from("/admin/servicios/{$service->id}/editar")
            ->delete("/admin/servicios/{$service->id}")
            ->assertRedirect("/admin/servicios/{$service->id}/editar")
            ->assertSessionHas('error');

        $this->assertModelExists($service);
    }

    public function test_tarifa_de_hora_extra_es_opcional(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)->post('/admin/servicios', $this->servicePayload($category, ['extra_hour_price' => '4500']))
            ->assertSessionHasNoErrors();
        $this->assertSame('4500.00', Service::firstWhere('name', 'Grupo Versátil Premium')->extra_hour_price);

        $this->actingAs($this->admin)->post('/admin/servicios', $this->servicePayload($category, ['name' => 'Sin horas', 'extra_hour_price' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull(Service::firstWhere('name', 'Sin horas')->extra_hour_price);
    }
}
