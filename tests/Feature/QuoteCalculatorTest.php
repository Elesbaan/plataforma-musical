<?php

namespace Tests\Feature;

use App\Models\CoverageZone;
use App\Services\QuoteCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class QuoteCalculatorTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    private function calculator(): QuoteCalculatorService
    {
        return app(QuoteCalculatorService::class);
    }

    public function test_calcula_partidas_cantidades_y_horas_extra(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $band = $this->catalogService(28500, overrides: ['name' => 'Grupo Versátil', 'extra_hour_price' => 4500]);
        $dancefloor = $this->catalogService(5500, capacity: 3, overrides: ['name' => 'Pista LED']);

        $result = $this->calculator()->calculate([
            ['service_id' => $band->id, 'quantity' => 1, 'extra_hours' => 2],
            ['service_id' => $dancefloor->id, 'quantity' => 2],
        ], $this->zone());

        $this->assertSame([
            ['service_id' => $band->id, 'name' => 'Grupo Versátil', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 28500.0, 'line_total' => 28500.0],
            ['service_id' => $band->id, 'name' => 'Hora extra — Grupo Versátil', 'kind' => 'extra_hours', 'quantity' => 2, 'unit_price' => 4500.0, 'line_total' => 9000.0],
            ['service_id' => $dancefloor->id, 'name' => 'Pista LED', 'kind' => 'service', 'quantity' => 2, 'unit_price' => 5500.0, 'line_total' => 11000.0],
        ], $result['lines']);
        $this->assertSame(48500.0, $result['subtotal']);
        $this->assertSame(48500.0, $result['total']);
    }

    public function test_sede_local_sin_viaticos(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(10000);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone(CoverageZone::TYPE_LOCAL_HQ));

        $this->assertSame(0.0, $result['travel_fee']);
        $this->assertFalse($result['travel_pending']);
        $this->assertSame(10000.0, $result['total']);
    }

    public function test_zona_foranea_con_viaticos_fijos(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(10000);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500));

        $this->assertSame(2500.0, $result['travel_fee']);
        $this->assertSame(12500.0, $result['total']);
    }

    public function test_zona_foranea_con_viaticos_porcentuales(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(28500);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone(CoverageZone::TYPE_FORANEA, 'percentage', 12.5));

        $this->assertSame(3562.5, $result['travel_fee']);
        $this->assertSame('12.5% del subtotal', $result['travel_label']);
        $this->assertSame(32062.5, $result['total']);
    }

    public function test_otra_ciudad_marca_viaticos_por_confirmar(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(10000);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone(CoverageZone::TYPE_CUSTOM_QUOTE, 'fixed', 999));

        $this->assertSame(0.0, $result['travel_fee']);
        $this->assertTrue($result['travel_pending']);
        $this->assertSame('Viáticos por confirmar según ubicación exacta', $result['travel_label']);
    }

    public function test_descuento_web_porcentual_sobre_subtotal_antes_de_viaticos(): void
    {
        $this->discountSettings(['online_discount_type' => 'percentage', 'online_discount_value' => 10]);
        $service = $this->catalogService(28500);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500));

        $this->assertTrue($result['discount_applies']);
        $this->assertSame(2850.0, $result['discount_amount']);
        $this->assertSame('Descuento exclusivo web (10%)', $result['discount_label']);
        $this->assertSame(28500.0 - 2850.0 + 2500.0, $result['total']);
    }

    public function test_descuento_web_fijo_no_supera_el_subtotal(): void
    {
        $this->discountSettings(['online_discount_type' => 'fixed', 'online_discount_value' => 1500]);
        $cheap = $this->catalogService(1000);
        $regular = $this->catalogService(20000);

        $this->assertSame(1000.0, $this->calculator()->calculate([['service_id' => $cheap->id, 'quantity' => 1]], $this->zone())['discount_amount']);
        $this->assertSame(1500.0, $this->calculator()->calculate([['service_id' => $regular->id, 'quantity' => 1]], $this->zone())['discount_amount']);
    }

    public function test_descuento_respeta_el_monto_minimo(): void
    {
        $this->discountSettings(['online_discount_min_amount' => 20000]);
        $service = $this->catalogService(15000);

        $below = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone());
        $this->assertFalse($below['discount_applies']);
        $this->assertSame(0.0, $below['discount_amount']);
        $this->assertSame(5000.0, $below['discount_missing']);
        $this->assertSame(15000.0, $below['total']);

        $atMinimum = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 2]], $this->zone());
        $this->assertTrue($atMinimum['discount_applies']);
        $this->assertSame(3000.0, $atMinimum['discount_amount']);
    }

    public function test_descuento_inactivo_no_se_aplica(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(28500);

        $result = $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1]], $this->zone());

        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertNull($result['discount_label']);
    }

    public function test_bloquea_servicio_sin_cupo_en_la_fecha(): void
    {
        $this->discountSettings();
        $date = now()->addMonth()->toDateString();
        $band = $this->catalogService(28500, capacity: 1, overrides: ['name' => 'Grupo Versátil']);
        $dancefloor = $this->catalogService(5500, capacity: 2);
        $this->reserve($band, $date);
        $this->reserve($dancefloor, $date);

        try {
            $this->calculator()->calculate([
                ['service_id' => $band->id, 'quantity' => 1],
                ['service_id' => $dancefloor->id, 'quantity' => 2],
            ], $this->zone(), $date);
            $this->fail('Debió rechazar servicios sin cupo.');
        } catch (ValidationException $e) {
            $this->assertStringStartsWith('Grupo Versátil ya no tiene disponibilidad para el', $e->errors()['items.0.quantity'][0]);
            $this->assertStringStartsWith('Solo quedan 1 disponibles', $e->errors()['items.1.quantity'][0]);
        }
    }

    public function test_rechaza_horas_extra_en_servicios_que_no_las_ofrecen(): void
    {
        $this->discountSettings();
        $service = $this->catalogService(5000);

        $this->expectException(ValidationException::class);
        $this->calculator()->calculate([['service_id' => $service->id, 'quantity' => 1, 'extra_hours' => 3]], $this->zone());
    }

    public function test_rechaza_servicios_inactivos_y_repetidos(): void
    {
        $this->discountSettings();
        $inactive = $this->catalogService(5000, overrides: ['is_active' => false]);
        $active = $this->catalogService(5000);

        try {
            $this->calculator()->calculate([
                ['service_id' => $inactive->id, 'quantity' => 1],
                ['service_id' => $active->id, 'quantity' => 1],
                ['service_id' => $active->id, 'quantity' => 1],
            ], $this->zone());
            $this->fail('Debió rechazar la cotización.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items.0.service_id', $e->errors());
            $this->assertArrayHasKey('items.2.service_id', $e->errors());
        }
    }

    public function test_endpoint_de_vista_previa_ignora_precios_enviados_por_el_cliente(): void
    {
        $this->discountSettings(['online_discount_active' => false]);
        $service = $this->catalogService(28500);

        $this->postJson('/api/cotizador/calcular', [
            'items' => [['service_id' => $service->id, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]],
            'coverage_zone_id' => $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500)->id,
            'subtotal' => 1,
            'total' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('subtotal', 28500)
            ->assertJsonPath('travel_fee', 2500)
            ->assertJsonPath('total', 31000);
    }

    public function test_endpoint_de_vista_previa_valida_partidas(): void
    {
        $this->postJson('/api/cotizador/calcular', ['items' => []])
            ->assertJsonValidationErrors(['items' => 'Agrega al menos un servicio a tu cotización.']);

        $this->postJson('/api/cotizador/calcular', ['items' => [['service_id' => 999, 'quantity' => 1]]])
            ->assertJsonValidationErrors(['items.0.service_id' => 'Uno de los servicios ya no está disponible.']);
    }
}
