<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class EventAgendaTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    private User $sales;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0));
        $this->sales = User::factory()->withRole(Role::SALES_COORDINATOR)->create();
        $this->date = '2026-12-12';
    }

    private function payload(array $services, array $overrides = []): array
    {
        return [
            'title' => 'Boda — Hacienda San Gabriel',
            'event_date' => $this->date,
            'start_time' => '18:00',
            'end_time' => '23:00',
            'city_name' => 'Metepec',
            'venue_name' => 'Hacienda San Gabriel',
            'client_name' => 'Ana López',
            'client_phone' => '7221234567',
            'agreed_total' => 40000,
            'deposit_paid' => 10000,
            'status' => 'reserved',
            'services' => collect($services)->map(fn ($qty, $id) => ['service_id' => $id, 'quantity' => $qty])->values()->all(),
            ...$overrides,
        ];
    }

    // ------------------------------------------------------------- Vistas

    public function test_vista_mensual_lista_y_alertas_de_capacidad(): void
    {
        $band = $this->catalogService(28500, capacity: 1, overrides: ['name' => 'Grupo Versátil']);
        $this->reserve($band, $this->date);
        $this->reserve($this->catalogService(1000, capacity: 3), '2027-01-10');

        $this->actingAs($this->sales)->get('/admin/agenda?mes=2026-12')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Events/Index')
                ->where('month', '2026-12')
                ->has('events', 1)
                ->where('events.0.event_date', $this->date)
                ->has('upcoming', 2)
                ->where("saturatedDays.{$this->date}.0.name", 'Grupo Versátil')
                ->where("saturatedDays.{$this->date}.0.level", 'full')
                ->where('abilities.manage', true)
                ->where('abilities.financials', true));
    }

    public function test_vista_completa_incluye_montos_y_saldo(): void
    {
        $service = $this->catalogService(1000, capacity: 2);
        $event = $this->reserve($service, $this->date);
        $event->update(['agreed_total' => 40000, 'deposit_paid' => 15000]);

        $this->actingAs($this->sales)->get("/admin/agenda/{$event->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Events/Show')
                ->where('event.agreed_total', '40000.00')
                ->where('event.deposit_paid', '15000.00')
                ->where('event.balance', '25000.00')
                ->where('event.client_name', 'Cliente'));
    }

    // ------------------------------------------------------------- Alta y edición manual

    public function test_crea_evento_manual_con_servicios(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $dancefloor = $this->catalogService(5500, capacity: 2);

        $response = $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([$band->id => 1, $dancefloor->id => 2]));

        $event = Event::with('services')->firstOrFail();
        $response->assertRedirect("/admin/agenda/{$event->id}")->assertSessionHas('success');
        $this->assertSame('reserved', $event->status);
        $this->assertSame('10000.00', $event->deposit_paid);
        $this->assertSame([$band->id => 1, $dancefloor->id => 2], $event->services->mapWithKeys(fn ($s) => [$s->id => $s->pivot->quantity])->all());
    }

    public function test_alerta_de_sobrecupo_y_confirmacion_bajo_criterio(): void
    {
        $band = $this->catalogService(28500, capacity: 1, overrides: ['name' => 'Grupo Versátil']);
        $this->reserve($band, $this->date);

        $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([$band->id => 1]))
            ->assertSessionHasErrors(['overbooking' => 'Sobrecupo en esta fecha: Grupo Versátil (2 de 1). Ajusta los servicios o confirma bajo tu criterio.']);
        $this->assertSame(1, Event::count());

        $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([$band->id => 1], ['force_overbooking' => true]))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Event::count());
    }

    public function test_alcanzar_la_capacidad_no_requiere_confirmacion(): void
    {
        $dancefloor = $this->catalogService(5500, capacity: 2);
        $this->reserve($dancefloor, $this->date);

        $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([$dancefloor->id => 1]))
            ->assertSessionHasNoErrors();
    }

    public function test_editar_no_cuenta_el_evento_contra_si_mismo(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $event = $this->reserve($band, $this->date);

        $this->actingAs($this->sales)->put("/admin/agenda/{$event->id}", $this->payload([$band->id => 1], ['deposit_paid' => 20000]))
            ->assertSessionHasNoErrors();

        $this->assertSame('20000.00', $event->fresh()->deposit_paid);
    }

    public function test_evento_cancelado_no_valida_capacidad(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $this->reserve($band, $this->date);

        $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([$band->id => 1], ['status' => 'cancelled']))
            ->assertSessionHasNoErrors();
    }

    public function test_validaciones_del_evento(): void
    {
        $this->actingAs($this->sales)->post('/admin/agenda', $this->payload([], ['deposit_paid' => 50000]))
            ->assertSessionHasErrors([
                'services' => 'Agrega al menos un servicio al evento.',
                'deposit_paid' => 'El anticipo no puede ser mayor al monto acordado.',
            ]);
    }

    public function test_endpoint_de_capacidad_excluye_el_evento_editado(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $event = $this->reserve($band, $this->date);

        $this->actingAs($this->sales)->getJson("/admin/agenda/capacidad?date={$this->date}")
            ->assertJsonPath("reserved.{$band->id}", 1);

        $this->actingAs($this->sales)->getJson("/admin/agenda/capacidad?date={$this->date}&exclude={$event->id}")
            ->assertJsonMissingPath("reserved.{$band->id}");
    }

    public function test_elimina_evento_y_libera_capacidad(): void
    {
        $band = $this->catalogService(28500, capacity: 1);
        $event = $this->reserve($band, $this->date);

        $this->actingAs($this->sales)->delete("/admin/agenda/{$event->id}")->assertRedirect('/admin/agenda');

        $this->assertModelMissing($event);
        $this->assertSame(0, $this->sales->newQuery()->getConnection()->table('event_services')->count());
    }

    // ------------------------------------------------------------- Permisos

    public function test_sin_permiso_de_agenda_no_accede(): void
    {
        $noRole = User::factory()->create();

        $this->actingAs($noRole)->get('/admin/agenda')->assertForbidden();
    }

    public function test_solo_lectura_no_puede_crear_ni_editar(): void
    {
        $partner = User::factory()->withRole(Role::PARTNER)->create();
        $service = $this->catalogService(1000);
        $event = $this->reserve($service, $this->date);

        $this->actingAs($partner)->get('/admin/agenda/crear')->assertForbidden();
        $this->actingAs($partner)->post('/admin/agenda', $this->payload([$service->id => 1]))->assertForbidden();
        $this->actingAs($partner)->put("/admin/agenda/{$event->id}", $this->payload([$service->id => 1]))->assertForbidden();
        $this->actingAs($partner)->delete("/admin/agenda/{$event->id}")->assertForbidden();
        $this->actingAs($partner)->getJson("/admin/agenda/capacidad?date={$this->date}")->assertForbidden();
    }
}
