<?php

namespace Tests\Feature\Admin;

use App\Models\CoverageZone;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoverageZonesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    private function zone(array $attributes = []): CoverageZone
    {
        return CoverageZone::create([
            'city_name' => 'CDMX',
            'state_name' => 'Ciudad de México',
            'zone_type' => 'foranea',
            'travel_fee_type' => 'fixed',
            'travel_fee_value' => 2500,
            'is_active' => true,
            'sort_order' => 1,
            ...$attributes,
        ]);
    }

    public function test_invitado_no_accede_a_zonas(): void
    {
        $this->get('/admin/zonas')->assertRedirect('/admin/login');
        $this->post('/admin/zonas', [])->assertRedirect('/admin/login');
    }

    public function test_lista_zonas_ordenadas(): void
    {
        $this->zone(['city_name' => 'Querétaro', 'sort_order' => 2]);
        $this->zone(['city_name' => 'Toluca', 'zone_type' => 'local_hq', 'sort_order' => 1]);

        $this->actingAs($this->admin)
            ->get('/admin/zonas')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Zones/Index')
                ->has('zones', 2)
                ->where('zones.0.city_name', 'Toluca')
                ->where('zones.0.quotes_count', 0));
    }

    public function test_crea_zona_foranea_con_viaticos_fijos(): void
    {
        $this->actingAs($this->admin)->post('/admin/zonas', [
            'city_name' => 'Valle de Bravo',
            'state_name' => 'Estado de México',
            'zone_type' => 'foranea',
            'travel_fee_type' => 'fixed',
            'travel_fee_value' => '2000',
            'is_active' => true,
            'sort_order' => 3,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $zone = CoverageZone::firstWhere('city_name', 'Valle de Bravo');
        $this->assertSame('2000.00', $zone->travel_fee_value);
        $this->assertSame('fixed', $zone->travel_fee_type);
    }

    public function test_sede_local_siempre_queda_sin_costo(): void
    {
        $this->actingAs($this->admin)->post('/admin/zonas', [
            'city_name' => 'Metepec',
            'zone_type' => 'local_hq',
            'travel_fee_type' => 'percentage',
            'travel_fee_value' => '999',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $zone = CoverageZone::firstWhere('city_name', 'Metepec');
        $this->assertSame('0.00', $zone->travel_fee_value);
        $this->assertSame('fixed', $zone->travel_fee_type);
    }

    public function test_zona_por_confirmar_queda_sin_costo(): void
    {
        $this->actingAs($this->admin)->post('/admin/zonas', [
            'city_name' => 'Otra ciudad',
            'zone_type' => 'custom_quote',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('0.00', CoverageZone::firstWhere('city_name', 'Otra ciudad')->travel_fee_value);
    }

    public function test_zona_foranea_requiere_viaticos_y_porcentaje_maximo_100(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/zonas', ['city_name' => 'Puebla', 'zone_type' => 'foranea', 'is_active' => true])
            ->assertSessionHasErrors(['travel_fee_type', 'travel_fee_value']);

        $this->actingAs($this->admin)
            ->post('/admin/zonas', [
                'city_name' => 'Puebla', 'zone_type' => 'foranea',
                'travel_fee_type' => 'percentage', 'travel_fee_value' => '120', 'is_active' => true,
            ])
            ->assertSessionHasErrors(['travel_fee_value' => 'Un porcentaje de viáticos no puede ser mayor a 100.']);

        $this->assertDatabaseMissing('coverage_zones', ['city_name' => 'Puebla']);
    }

    public function test_actualiza_zona_y_puede_convertirse_en_sede_local(): void
    {
        $zone = $this->zone();

        $this->actingAs($this->admin)->put("/admin/zonas/{$zone->id}", [
            'city_name' => 'CDMX',
            'zone_type' => 'local_hq',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $zone->refresh();
        $this->assertSame('local_hq', $zone->zone_type);
        $this->assertSame('0.00', $zone->travel_fee_value);
    }

    public function test_elimina_zona_sin_cotizaciones(): void
    {
        $zone = $this->zone();

        $this->actingAs($this->admin)->delete("/admin/zonas/{$zone->id}")->assertSessionHas('success');

        $this->assertModelMissing($zone);
    }

    public function test_no_elimina_zona_con_cotizaciones(): void
    {
        $zone = $this->zone();
        Quote::create([
            'folio' => 'COT-2026-0001', 'client_name' => 'Ana', 'client_email' => 'ana@ejemplo.mx',
            'client_phone' => '7220000000', 'event_date' => '2026-12-12', 'event_type' => 'Boda',
            'coverage_zone_id' => $zone->id, 'subtotal' => 1000, 'total' => 1000, 'valid_until' => '2026-12-01',
        ]);

        $this->actingAs($this->admin)
            ->from('/admin/zonas')
            ->delete("/admin/zonas/{$zone->id}")
            ->assertRedirect('/admin/zonas')
            ->assertSessionHas('error');

        $this->assertModelExists($zone);
    }
}
