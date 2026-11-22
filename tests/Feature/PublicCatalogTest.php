<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoverageZone;
use App\Models\ServiceMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->discountSettings(['hero_video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    }

    public function test_inicio_muestra_destacados_por_categoria_y_propuesta_de_valor(): void
    {
        $this->zone(CoverageZone::TYPE_LOCAL_HQ, overrides: ['city_name' => 'Toluca / Metepec']);
        $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500);
        $this->zone(CoverageZone::TYPE_CUSTOM_QUOTE);
        $band = $this->catalogService(28500, overrides: ['name' => 'Grupo Versátil', 'is_featured' => true]);
        $band->media()->create(['media_type' => ServiceMedia::TYPE_IMAGE, 'file_path' => 'services/images/a.webp', 'is_cover' => true]);
        $band->media()->create(['media_type' => ServiceMedia::TYPE_VIDEO_EMBED, 'embed_url' => 'https://player.vimeo.com/video/123456789']);
        $this->catalogService(5500, overrides: ['name' => 'Pista LED', 'service_type' => 'addon_extra', 'is_featured' => true]);
        $this->catalogService(1000, overrides: ['name' => 'No destacado']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->has('featured', 1)
                ->has('featured.0.services', 2)
                ->where('featured.0.services.0.name', 'Grupo Versátil')
                ->where('featured.0.services.0.cover_url', asset('uploads/services/images/a.webp'))
                ->where('featured.0.services.0.has_video', true)
                ->where('highlights.base_city', 'Toluca / Metepec')
                ->where('highlights.zones_count', 2)
                ->where('highlights.accepts_other_cities', true)
                ->where('highlights.own_count', 2)
                ->where('highlights.partner_count', 1)
                ->where('heroVideoEmbed', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
                ->has('eventTypes'));
    }

    public function test_catalogo_oculta_servicios_inactivos_y_de_categorias_inactivas(): void
    {
        $this->catalogService(1000, overrides: ['name' => 'Visible']);
        $this->catalogService(1000, overrides: ['name' => 'Inactivo', 'is_active' => false]);
        $hidden = Category::create(['name' => 'Oculta', 'slug' => 'oculta', 'is_active' => false]);
        $this->catalogService(1000, overrides: ['name' => 'En categoría oculta', 'category_id' => $hidden->id]);

        $this->get('/servicios')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/Index')
                ->has('services.data', 1)
                ->where('services.data.0.name', 'Visible')
                ->has('categories', 1));
    }

    public function test_filtra_por_categoria_busqueda_y_destacados(): void
    {
        $regional = Category::create(['name' => 'Regional', 'slug' => 'regional', 'is_active' => true]);
        $mariachi = $this->catalogService(4800, overrides: ['name' => 'Mariachi Tradicional', 'category_id' => $regional->id]);
        $mariachi->features()->create(['feature_text' => 'Serenata incluida', 'sort_order' => 1]);
        $this->catalogService(28500, overrides: ['name' => 'Grupo Versátil', 'is_featured' => true]);

        $this->get('/servicios?categoria=regional')
            ->assertInertia(fn (Assert $page) => $page->has('services.data', 1)->where('services.data.0.name', 'Mariachi Tradicional'));

        $this->get('/servicios?q=serenata')
            ->assertInertia(fn (Assert $page) => $page->has('services.data', 1)->where('services.data.0.name', 'Mariachi Tradicional'));

        $this->get('/servicios?destacados=1')
            ->assertInertia(fn (Assert $page) => $page->has('services.data', 1)->where('services.data.0.name', 'Grupo Versátil'));
    }

    public function test_ordena_por_precio(): void
    {
        $this->catalogService(9000, overrides: ['name' => 'Medio']);
        $this->catalogService(1000, overrides: ['name' => 'Barato']);
        $this->catalogService(50000, overrides: ['name' => 'Caro']);

        $this->get('/servicios?orden=precio-asc')
            ->assertInertia(fn (Assert $page) => $page->where('services.data.0.name', 'Barato')->where('services.data.2.name', 'Caro'));

        $this->get('/servicios?orden=precio-desc')
            ->assertInertia(fn (Assert $page) => $page->where('services.data.0.name', 'Caro'));

        $this->get('/servicios?orden=invalido')->assertOk();
    }

    public function test_ficha_de_servicio_con_galeria_caracteristicas_y_hora_extra(): void
    {
        $service = $this->catalogService(28500, overrides: ['name' => 'Grupo Versátil Premium', 'extra_hour_price' => 4500, 'full_description' => 'Show completo.']);
        $service->features()->create(['feature_text' => 'Sonido profesional', 'sort_order' => 1]);
        $service->media()->create(['media_type' => ServiceMedia::TYPE_VIDEO_LOCAL, 'file_path' => 'services/videos/show.mp4', 'sort_order' => 1]);
        $service->media()->create(['media_type' => ServiceMedia::TYPE_IMAGE, 'file_path' => 'services/images/portada.webp', 'is_cover' => true, 'sort_order' => 2]);
        $related = $this->catalogService(6500, overrides: ['name' => 'Teclado y Voz']);

        $this->get('/servicios/grupo-versatil-premium')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/Show')
                ->where('service.name', 'Grupo Versátil Premium')
                ->where('service.extra_hour_price', '4500.00')
                ->where('service.features', ['Sonido profesional'])
                ->has('service.media', 2)
                ->where('service.media.0.media_type', 'image')
                ->where('service.media.1.url', asset('uploads/services/videos/show.mp4'))
                ->has('related', 1)
                ->where('related.0.id', $related->id));
    }

    public function test_ficha_de_servicio_inactivo_o_inexistente_es_404(): void
    {
        $this->catalogService(1000, overrides: ['name' => 'Oculto', 'is_active' => false]);

        $this->get('/servicios/oculto')->assertNotFound();
        $this->get('/servicios/no-existe')->assertNotFound();
    }

    public function test_paquetes_redirige_a_destacados(): void
    {
        $this->get('/paquetes')->assertRedirect('/servicios?destacados=1');
    }
}
