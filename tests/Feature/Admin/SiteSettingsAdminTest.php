<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake(UploadService::DISK);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'brand_name' => 'Marca Editada',
            'tagline' => 'Nuevo eslogan',
            'seo_description' => 'Descripción',
            'hero_title' => 'Título',
            'hero_subtitle' => 'Subtítulo',
            'hero_video_url' => '',
            'color_primary' => '#112233',
            'color_secondary' => '#445566',
            'color_accent' => '#778899',
            'whatsapp_number' => '52 722 123 4567',
            'phone_number' => '722 123 4567',
            'contact_email' => 'hola@ejemplo.mx',
            'address_text' => 'Toluca',
            'facebook_url' => 'https://facebook.com/marca',
            'instagram_url' => '',
            'tiktok_url' => '',
            'youtube_url' => '',
            'spotify_url' => '',
            'online_discount_active' => '1',
            'online_discount_type' => 'percentage',
            'online_discount_value' => '15',
            'online_discount_min_amount' => '5000',
            'quote_validity_days' => '10',
            'quote_terms_text' => 'Términos',
            ...$overrides,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_invitado_no_accede_a_identidad(): void
    {
        $this->get('/admin/identidad')->assertRedirect('/admin/login');
        $this->put('/admin/identidad', $this->payload())->assertRedirect('/admin/login');
    }

    public function test_formulario_muestra_configuracion_actual(): void
    {
        SiteSetting::create(['brand_name' => 'Marca Actual']);

        $this->actingAs($this->admin())
            ->get('/admin/identidad')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Edit')
                ->where('settings.brand_name', 'Marca Actual'));
    }

    public function test_actualiza_identidad_y_normaliza_datos(): void
    {
        SiteSetting::create(['brand_name' => 'Marca Actual']);

        $this->actingAs($this->admin())
            ->put('/admin/identidad', $this->payload(['color_primary' => '#abcdef']))
            ->assertRedirect('/admin/identidad')
            ->assertSessionHas('success');

        $settings = SiteSetting::first();
        $this->assertSame('Marca Editada', $settings->brand_name);
        $this->assertSame('#ABCDEF', $settings->color_primary);
        $this->assertSame('527221234567', $settings->whatsapp_number);
        $this->assertSame('15.00', $settings->online_discount_value);
        $this->assertNull($settings->instagram_url);
        $this->assertSame(1, SiteSetting::count());
    }

    public function test_crea_el_registro_si_no_existia(): void
    {
        $this->actingAs($this->admin())->put('/admin/identidad', $this->payload())->assertSessionHasNoErrors();

        $this->assertSame('Marca Editada', SiteSetting::first()->brand_name);
    }

    public function test_guardar_invalida_la_cache_y_el_sitio_refleja_el_cambio(): void
    {
        SiteSetting::create(['brand_name' => 'Marca Actual']);
        SiteSetting::current();
        $this->assertTrue(Cache::has(SiteSetting::CACHE_KEY));

        $this->actingAs($this->admin())->put('/admin/identidad', $this->payload());

        $this->assertFalse(Cache::has(SiteSetting::CACHE_KEY));
        $this->get('/')
            ->assertSee('--color-primary: #112233;', false)
            ->assertInertia(fn (Assert $page) => $page->where('siteSettings.brand_name', 'Marca Editada'));
    }

    public function test_validaciones_en_espanol(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/identidad', $this->payload([
                'brand_name' => '',
                'color_primary' => 'rojo',
                'online_discount_value' => '150',
                'whatsapp_number' => '123',
                'facebook_url' => 'no-es-url',
            ]))
            ->assertSessionHasErrors([
                'brand_name' => 'El nombre comercial es obligatorio.',
                'color_primary' => 'Elige un color primario válido (formato #RRGGBB).',
                'online_discount_value' => 'Un descuento porcentual no puede ser mayor a 100.',
                'whatsapp_number',
                'facebook_url',
            ]);
    }

    public function test_descuento_fijo_permite_montos_mayores_a_100(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/identidad', $this->payload(['online_discount_type' => 'fixed', 'online_discount_value' => '1500']))
            ->assertSessionHasNoErrors();

        $this->assertSame('1500.00', SiteSetting::first()->online_discount_value);
    }

    public function test_sube_logos_y_favicon_en_public_uploads_y_reemplaza_el_anterior(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/identidad', $this->payload([
            'logo_light' => UploadedFile::fake()->image('claro.png', 600, 200),
            'logo_dark' => UploadedFile::fake()->image('oscuro.png', 600, 200),
            'favicon' => UploadedFile::fake()->image('icono.png', 64, 64),
        ]))->assertSessionHasNoErrors();

        $settings = SiteSetting::first();
        foreach (['logo_light_path', 'logo_dark_path', 'favicon_path'] as $column) {
            $this->assertStringStartsWith('branding/', $settings->{$column});
            Storage::disk(UploadService::DISK)->assertExists($settings->{$column});
        }
        $this->assertSame(asset('uploads/'.$settings->logo_light_path), $settings->logo_light_url);

        $oldLight = $settings->logo_light_path;
        $this->actingAs($admin)->put('/admin/identidad', $this->payload([
            'logo_light' => UploadedFile::fake()->image('nuevo.png', 600, 200),
        ]));

        Storage::disk(UploadService::DISK)->assertMissing($oldLight);
        Storage::disk(UploadService::DISK)->assertExists(SiteSetting::first()->logo_light_path);
    }

    public function test_quitar_logo_elimina_el_archivo(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/identidad', $this->payload([
            'logo_dark' => UploadedFile::fake()->image('oscuro.png'),
        ]));
        $path = SiteSetting::first()->logo_dark_path;

        $this->actingAs($admin)->put('/admin/identidad', $this->payload(['remove_logo_dark' => '1']));

        $this->assertNull(SiteSetting::first()->logo_dark_path);
        Storage::disk(UploadService::DISK)->assertMissing($path);
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/identidad', $this->payload([
                'logo_light' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
                'favicon' => UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
            ]))
            ->assertSessionHasErrors(['logo_light', 'favicon']);
    }
}
