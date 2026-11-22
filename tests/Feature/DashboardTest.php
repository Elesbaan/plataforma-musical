<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0));
    }

    public function test_ventas_ve_cotizaciones_montos_eventos_y_saldo(): void
    {
        $service = $this->catalogService(10000, capacity: 5);
        $this->makeQuote([[$service, 1]], ['status' => 'new', 'total' => 10000]);
        $this->makeQuote([[$service, 2]], ['status' => 'contacted', 'total' => 20000]);
        $this->makeQuote([[$service, 1]], ['status' => 'new', 'total' => 99999, 'created_at' => now()->subMonths(2)]);

        $this->reserve($service, '2026-11-28')->update(['agreed_total' => 30000, 'deposit_paid' => 10000]);
        $this->reserve($service, '2027-02-01')->update(['agreed_total' => 15000, 'deposit_paid' => 0]);
        $this->reserve($service, '2026-11-29', status: 'cancelled')->update(['agreed_total' => 50000]);

        $this->actingAs(User::factory()->withRole(Role::SALES_COORDINATOR)->create())
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.new_quotes', 2)
                ->where('stats.month_quoted', '30000.00')
                ->where('stats.month_quotes_count', 2)
                ->where('stats.upcoming_events', 2)
                ->where('stats.pending_balance', '35000.00')
                ->has('latestQuotes', 3)
                ->has('weekEvents', 1)
                ->where('weekEvents.0.event_date', '2026-11-28')
                ->where('myServices', null)
                ->missing('stats.my_upcoming_events'));
    }

    public function test_proveedor_ve_solo_sus_eventos_y_servicios(): void
    {
        $own = $this->catalogService(4800, capacity: 2, overrides: ['name' => 'Mariachi']);
        $foreign = $this->catalogService(28500, capacity: 2);
        $partner = User::factory()->withRole(Role::PARTNER)->create();
        $partner->services()->attach($own);

        $this->reserve($own, '2026-12-05');
        $this->reserve($foreign, '2026-12-06');
        $this->makeQuote([[$foreign, 1]]);

        $this->actingAs($partner)->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.my_upcoming_events', 1)
                ->missing('stats.new_quotes')
                ->missing('stats.month_quoted')
                ->missing('stats.pending_balance')
                ->missing('stats.upcoming_events')
                ->where('latestQuotes', null)
                ->has('weekEvents', 1)
                ->missing('weekEvents.0.agreed_total')
                ->missing('weekEvents.0.client_name')
                ->has('myServices', 1)
                ->where('myServices.0.name', 'Mariachi'));
    }

    public function test_usuario_sin_permisos_ve_dashboard_vacio(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats', [])
                ->where('latestQuotes', null)
                ->where('weekEvents', null)
                ->where('myServices', null));
    }
}
