<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Quote;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->date = now()->addMonth()->toDateString();
    }

    public function test_servicio_se_bloquea_al_alcanzar_su_capacidad_diaria(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $this->reserve($band, $this->date);

        $status = app(AvailabilityService::class)->forDate($this->date)[$band->id];

        $this->assertSame(['capacity' => 1, 'reserved' => 1, 'remaining' => 0, 'available' => false], $status);
    }

    public function test_capacidad_mayor_a_uno_permite_varios_eventos_el_mismo_dia(): void
    {
        $dancefloor = $this->catalogService(5500, capacity: 2);
        $this->reserve($dancefloor, $this->date);

        $service = app(AvailabilityService::class);
        $this->assertSame(1, $service->remaining($dancefloor, $this->date));

        $this->reserve($dancefloor, $this->date);
        $this->assertSame(0, $service->remaining($dancefloor, $this->date));
    }

    public function test_cantidad_reservada_cuenta_como_unidades(): void
    {
        $dancefloor = $this->catalogService(5500, capacity: 3);
        $this->reserve($dancefloor, $this->date, quantity: 2);

        $this->assertSame(1, app(AvailabilityService::class)->remaining($dancefloor, $this->date));
    }

    public function test_otra_fecha_sigue_disponible(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $this->reserve($band, $this->date);

        $otherDay = now()->addMonth()->addDay()->toDateString();
        $this->assertTrue(app(AvailabilityService::class)->forDate($otherDay)[$band->id]['available']);
    }

    public function test_eventos_cancelados_o_completados_no_bloquean(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $this->reserve($band, $this->date, status: Event::STATUS_CANCELLED);
        $this->reserve($band, $this->date, status: Event::STATUS_COMPLETED);

        $this->assertTrue(app(AvailabilityService::class)->forDate($this->date)[$band->id]['available']);
    }

    public function test_cotizaciones_no_bloquean_fechas(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $quote = Quote::create([
            'folio' => 'COT-2026-0001', 'client_name' => 'Ana', 'client_email' => 'a@x.mx', 'client_phone' => '7220000000',
            'event_date' => $this->date, 'event_type' => 'Boda', 'subtotal' => 28500, 'total' => 28500,
            'valid_until' => now()->addWeek(), 'status' => 'new',
        ]);
        $quote->items()->create(['service_id' => $band->id, 'service_name_snapshot' => $band->name, 'quantity' => 1, 'unit_price' => 28500, 'line_total' => 28500]);

        $this->assertTrue(app(AvailabilityService::class)->forDate($this->date)[$band->id]['available']);
    }

    public function test_servicios_inactivos_no_se_ofrecen(): void
    {
        $inactive = $this->catalogService(1000, overrides: ['is_active' => false]);

        $this->assertArrayNotHasKey($inactive->id, app(AvailabilityService::class)->forDate($this->date));
    }

    public function test_endpoint_publico_de_disponibilidad(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $dancefloor = $this->catalogService(5500, capacity: 2);
        $this->reserve($band, $this->date);

        $this->getJson("/api/availability?date={$this->date}")
            ->assertOk()
            ->assertJsonPath('date', $this->date)
            ->assertJsonFragment(['id' => $band->id, 'remaining' => 0, 'available' => false])
            ->assertJsonFragment(['id' => $dancefloor->id, 'remaining' => 2, 'available' => true]);
    }

    public function test_endpoint_valida_la_fecha(): void
    {
        $this->getJson('/api/availability')->assertJsonValidationErrors(['date' => 'Elige la fecha de tu evento.']);
        $this->getJson('/api/availability?date=2020-01-01')->assertJsonValidationErrors(['date' => 'Elige una fecha a partir de hoy.']);
        $this->getJson('/api/availability?date=mañana')->assertJsonValidationErrors('date');
    }
}
