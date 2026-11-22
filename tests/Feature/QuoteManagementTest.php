<?php

namespace Tests\Feature;

use App\Mail\QuoteSentToClient;
use App\Models\CoverageZone;
use App\Models\Permission;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class QuoteManagementTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake(UploadService::DISK);
        $this->discountSettings();
        $this->sales = User::factory()->withRole(Role::SALES_COORDINATOR)->create();
    }

    // ------------------------------------------------------------- Listado y detalle

    public function test_listado_con_filtros_de_estatus_fechas_zona_y_busqueda(): void
    {
        $band = $this->catalogService(28500);
        $cdmx = $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500, ['city_name' => 'CDMX']);
        $this->makeQuote([[$band, 1]], ['client_name' => 'Ana López', 'status' => 'new', 'event_date' => '2026-12-12']);
        $this->makeQuote([[$band, 1]], ['client_name' => 'Luis Pérez', 'client_phone' => '5598765432', 'status' => 'contacted', 'event_date' => '2027-03-01', 'coverage_zone_id' => $cdmx->id]);

        $this->actingAs($this->sales)->get('/admin/cotizaciones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Quotes/Index')
                ->has('quotes.data', 2)
                ->where('statusCounts.new', 1)
                ->where('statusCounts.contacted', 1));

        $this->actingAs($this->sales)->get('/admin/cotizaciones?status=contacted')
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1)->where('quotes.data.0.client_name', 'Luis Pérez'));

        $this->actingAs($this->sales)->get('/admin/cotizaciones?from=2027-01-01&to=2027-12-31')
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1)->where('quotes.data.0.city', 'CDMX'));

        $this->actingAs($this->sales)->get("/admin/cotizaciones?zone={$cdmx->id}")
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1));

        $this->actingAs($this->sales)->get('/admin/cotizaciones?search=98765')
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1)->where('quotes.data.0.client_name', 'Luis Pérez'));

        $folio = Quote::firstWhere('client_name', 'Ana López')->folio;
        $this->actingAs($this->sales)->get("/admin/cotizaciones?search={$folio}")
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1)->where('quotes.data.0.client_name', 'Ana López'));
    }

    public function test_detalle_con_partidas_horas_extra_y_permisos(): void
    {
        $band = $this->catalogService(28500, overrides: ['extra_hour_price' => 4500]);
        $quote = $this->makeQuote([[$band, 1, 2]]);

        $this->actingAs($this->sales)->get("/admin/cotizaciones/{$quote->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Quotes/Show')
                ->where('quote.folio', $quote->folio)
                ->has('quote.items', 2)
                ->where('quote.items.1.line_type', 'extra_hours')
                ->where('quote.items.1.quantity', 2)
                ->where('abilities.manage', true)
                ->where('abilities.convert', true));
    }

    // ------------------------------------------------------------- Seguimiento

    public function test_cambia_estatus_de_seguimiento(): void
    {
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($this->sales)->patch("/admin/cotizaciones/{$quote->id}/estatus", ['status' => 'contacted'])
            ->assertSessionHas('success');
        $this->assertSame('contacted', $quote->fresh()->status);

        $this->actingAs($this->sales)->patch("/admin/cotizaciones/{$quote->id}/estatus", ['status' => 'cancelled']);
        $this->assertSame('cancelled', $quote->fresh()->status);

        $this->actingAs($this->sales)->patch("/admin/cotizaciones/{$quote->id}/estatus", ['status' => 'perdida'])
            ->assertSessionHasErrors('status');
    }

    public function test_confirmada_solo_se_asigna_al_agendar_el_evento(): void
    {
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($this->sales)->patch("/admin/cotizaciones/{$quote->id}/estatus", ['status' => 'confirmed'])
            ->assertSessionHas('error', 'Para marcarla como confirmada, usa “Confirmar y agendar evento”.');

        $this->assertSame('new', $quote->fresh()->status);
    }

    public function test_guarda_notas_internas(): void
    {
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($this->sales)->patch("/admin/cotizaciones/{$quote->id}/notas", ['admin_notes' => 'Llamar el lunes.'])
            ->assertSessionHas('success');

        $this->assertSame('Llamar el lunes.', $quote->fresh()->admin_notes);
    }

    public function test_reenvia_pdf_por_correo_y_permite_descargarlo(): void
    {
        Mail::fake();
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($this->sales)->post("/admin/cotizaciones/{$quote->id}/reenviar")
            ->assertSessionHas('success', "Se reenvió la cotización a {$quote->client_email}.");

        Mail::assertSent(QuoteSentToClient::class, fn ($mail) => $mail->hasTo($quote->client_email));
        $this->assertNotNull($quote->fresh()->pdf_path);

        $this->actingAs($this->sales)->get("/admin/cotizaciones/{$quote->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ------------------------------------------------------------- Permisos

    public function test_solo_ver_no_permite_atender(): void
    {
        $role = Role::create(['name' => 'Consulta', 'slug' => 'consulta']);
        $role->permissions()->attach(Permission::where('key', 'quotes.view')->value('id'));
        $viewer = User::factory()->create(['role_id' => $role->id]);
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($viewer)->get("/admin/cotizaciones/{$quote->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('abilities.manage', false)->where('abilities.convert', false));

        $this->actingAs($viewer)->patch("/admin/cotizaciones/{$quote->id}/estatus", ['status' => 'contacted'])->assertForbidden();
        $this->actingAs($viewer)->patch("/admin/cotizaciones/{$quote->id}/notas", ['admin_notes' => 'x'])->assertForbidden();
        $this->actingAs($viewer)->post("/admin/cotizaciones/{$quote->id}/reenviar")->assertForbidden();
    }

    public function test_sin_permiso_de_cotizaciones_no_accede(): void
    {
        $partner = User::factory()->withRole(Role::PARTNER)->create();
        $quote = $this->makeQuote([[$this->catalogService(1000), 1]]);

        $this->actingAs($partner)->get('/admin/cotizaciones')->assertForbidden();
        $this->actingAs($partner)->get("/admin/cotizaciones/{$quote->id}")->assertForbidden();
        $this->actingAs($partner)->get("/admin/cotizaciones/{$quote->id}/pdf")->assertForbidden();
    }
}
