<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoverageZone;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_seeder_crea_administrador_con_rol_super_admin(): void
    {
        $admin = User::where('email', 'admin@colectivomusical.mx')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
    }

    public function test_seeder_crea_configuracion_global_con_descuento_web(): void
    {
        $settings = SiteSetting::first();

        $this->assertNotNull($settings);
        $this->assertTrue($settings->online_discount_active);
        $this->assertSame('percentage', $settings->online_discount_type);
        $this->assertSame('10.00', $settings->online_discount_value);
    }

    public function test_seeder_crea_zonas_de_cobertura_con_tipos_correctos(): void
    {
        $this->assertSame(5, CoverageZone::count());
        $this->assertSame('0.00', CoverageZone::where('zone_type', 'local_hq')->first()->travel_fee_value);
        $this->assertSame(3, CoverageZone::where('zone_type', 'foranea')->count());
        $this->assertSame(1, CoverageZone::where('zone_type', 'custom_quote')->count());
    }

    public function test_seeder_crea_catalogo_con_caracteristicas_y_capacidad(): void
    {
        $this->assertSame(5, Category::count());
        $this->assertGreaterThanOrEqual(8, Service::count());

        Service::with('features')->get()->each(function (Service $service) {
            $this->assertGreaterThanOrEqual(1, $service->daily_capacity);
            $this->assertNotEmpty($service->features, "{$service->name} sin características");
        });
    }

    public function test_guardar_site_setting_invalida_la_cache(): void
    {
        Cache::put('site_settings', 'valor-viejo');

        SiteSetting::first()->update(['brand_name' => 'Nueva Marca']);

        $this->assertFalse(Cache::has('site_settings'));
    }
}
