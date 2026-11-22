<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createSettings(array $overrides = []): SiteSetting
    {
        return SiteSetting::create([
            'brand_name' => 'Marca de Prueba',
            'tagline' => 'Eslogan de prueba',
            'seo_description' => 'Descripción SEO de prueba',
            'color_primary' => '#112233',
            'color_secondary' => '#445566',
            'color_accent' => '#778899',
            'whatsapp_number' => '527220000000',
            ...$overrides,
        ]);
    }

    public function test_inicio_comparte_configuracion_de_marca_con_inertia(): void
    {
        $this->createSettings();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('siteSettings.brand_name', 'Marca de Prueba')
                ->where('siteSettings.color_primary', '#112233')
                ->where('siteSettings.whatsapp_number', '527220000000')
                ->where('auth.user', null)
                ->has('flash')
            );
    }

    public function test_vista_raiz_inyecta_titulo_seo_y_variables_css(): void
    {
        $this->createSettings();

        $this->get('/')
            ->assertOk()
            ->assertSee('<title inertia>Marca de Prueba</title>', false)
            ->assertSee('<meta name="description" content="Descripción SEO de prueba">', false)
            ->assertSee('--color-primary: #112233;', false)
            ->assertSee('--color-accent-rgb: 119 136 153;', false)
            ->assertSee('rel="icon"', false);
    }

    public function test_favicon_dinamico_se_inyecta_cuando_existe(): void
    {
        $this->createSettings(['favicon_path' => 'branding/favicon.png']);

        $this->get('/')->assertSee('branding/favicon.png', false);
    }

    public function test_color_invalido_se_reemplaza_por_valor_por_defecto(): void
    {
        $this->createSettings(['color_primary' => 'red;}</style><script>alert(1)</script>']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--color-primary: #6D28D9;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_configuracion_se_cachea_y_se_refresca_al_guardar(): void
    {
        $settings = $this->createSettings();

        $this->assertSame('Marca de Prueba', SiteSetting::current()->brand_name);
        $this->assertTrue(Cache::has(SiteSetting::CACHE_KEY));

        $settings->update(['brand_name' => 'Marca Renovada']);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('siteSettings.brand_name', 'Marca Renovada'));
    }

    public function test_sitio_funciona_sin_configuracion_registrada(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('--color-primary: #6D28D9;', false);
    }

    public function test_aviso_de_privacidad_esta_disponible(): void
    {
        $this->createSettings();

        $this->get('/aviso-de-privacidad')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Privacy'));
    }
}
