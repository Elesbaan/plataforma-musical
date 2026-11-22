<?php

namespace Tests\Feature;

use App\Mail\NewQuoteNotification;
use App\Mail\QuoteSentToClient;
use App\Models\CoverageZone;
use App\Models\Quote;
use App\Models\Service;
use App\Services\UploadService;
use App\Support\QuoteLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsQuoteFixtures;
use Tests\TestCase;

class QuoteSubmissionTest extends TestCase
{
    use BuildsQuoteFixtures;
    use RefreshDatabase;

    private Service $band;

    private Service $dancefloor;

    private CoverageZone $foranea;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        Storage::fake(UploadService::DISK);
        $this->travelTo(now()->setDate(2026, 11, 25));

        $this->discountSettings(['online_discount_type' => 'percentage', 'online_discount_value' => 10, 'quote_validity_days' => 7]);
        $this->band = $this->catalogService(28500, capacity: 1, overrides: ['name' => 'Grupo Versátil Premium', 'extra_hour_price' => 4500]);
        $this->dancefloor = $this->catalogService(5500, capacity: 2, overrides: ['name' => 'Pista Luminosa LED']);
        $this->foranea = $this->zone(CoverageZone::TYPE_FORANEA, 'fixed', 2500, ['city_name' => 'CDMX']);
        $this->date = '2026-12-12';
    }

    private function payload(array $overrides = []): array
    {
        return [
            'event_date' => $this->date,
            'event_time' => '18:30',
            'event_type' => 'Boda',
            'venue_name' => 'Hacienda San Gabriel',
            'coverage_zone_id' => $this->foranea->id,
            'client_name' => 'Ana López Martínez',
            'client_email' => 'Ana@Ejemplo.mx',
            'client_phone' => '722 123 4567',
            'accept_privacy' => true,
            'items' => [
                ['service_id' => $this->band->id, 'quantity' => 1, 'extra_hours' => 1],
                ['service_id' => $this->dancefloor->id, 'quantity' => 2, 'extra_hours' => 0],
            ],
            ...$overrides,
        ];
    }

    public function test_cotizador_carga_servicios_zonas_y_preseleccion(): void
    {
        $this->get('/cotizador?servicio=pista-luminosa-led&tipo=Boda')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/QuoteBuilder')
                ->has('categories.0.services', 2)
                ->where('categories.0.services.0.daily_capacity', 1)
                ->has('zones', 1)
                ->where('preselect.service', 'pista-luminosa-led')
                ->where('preselect.eventType', 'Boda')
                ->where('today', '2026-11-25'));
    }

    public function test_genera_cotizacion_con_folio_pdf_y_correos(): void
    {
        $response = $this->post('/cotizador', $this->payload());

        $quote = Quote::with('items')->firstOrFail();
        $response->assertRedirect(QuoteLinks::confirmation($quote));

        // Folio y datos normalizados
        $this->assertSame('COT-2026-0001', $quote->folio);
        $this->assertSame('ana@ejemplo.mx', $quote->client_email);
        $this->assertSame('7221234567', $quote->client_phone);
        $this->assertSame('2026-12-02', $quote->valid_until->toDateString()); // emisión + 7 días
        $this->assertSame('new', $quote->status);

        // Recálculo en servidor: 28,500 + 4,500 (hora extra) + 11,000 = 44,000; −10% = 4,400; +2,500
        $this->assertSame('44000.00', $quote->subtotal);
        $this->assertSame('4400.00', $quote->discount_amount);
        $this->assertSame('2500.00', $quote->travel_fee);
        $this->assertSame('42100.00', $quote->total);
        $this->assertSame(
            ['Grupo Versátil Premium', 'Hora extra — Grupo Versátil Premium', 'Pista Luminosa LED'],
            $quote->items->pluck('service_name_snapshot')->all()
        );

        // PDF guardado en public/uploads/quotes con nombre no adivinable
        $this->assertMatchesRegularExpression('#^quotes/COT-2026-0001-[A-Za-z0-9]{24}\.pdf$#', $quote->pdf_path);
        Storage::disk(UploadService::DISK)->assertExists($quote->pdf_path);
        $this->assertStringStartsWith('%PDF', Storage::disk(UploadService::DISK)->get($quote->pdf_path));

        // Correos con el PDF adjunto
        Mail::assertSent(QuoteSentToClient::class, fn ($mail) => $mail->hasTo('ana@ejemplo.mx')
            && $mail->quote->is($quote)
            && $mail->hasAttachment(Attachment::fromStorageDisk(UploadService::DISK, $quote->pdf_path)->as('COT-2026-0001.pdf')->withMime('application/pdf')));
        Mail::assertSent(NewQuoteNotification::class, fn ($mail) => $mail->hasTo('agencia@ejemplo.mx')
            && $mail->hasReplyTo('ana@ejemplo.mx'));
    }

    public function test_folio_es_secuencial_por_anio(): void
    {
        $this->post('/cotizador', $this->payload());
        $this->post('/cotizador', $this->payload(['client_name' => 'Luis Pérez']));

        $this->assertSame(['COT-2026-0001', 'COT-2026-0002'], Quote::orderBy('id')->pluck('folio')->all());

        $this->travelTo(now()->setDate(2027, 1, 5));
        $this->post('/cotizador', $this->payload(['event_date' => '2027-02-14']));

        $this->assertSame('COT-2027-0001', Quote::latest('id')->value('folio'));
    }

    public function test_ignora_precios_y_totales_manipulados_desde_el_navegador(): void
    {
        $this->post('/cotizador', $this->payload([
            'subtotal' => 1,
            'discount_amount' => 999999,
            'travel_fee' => 0,
            'total' => 1,
            'items' => [['service_id' => $this->band->id, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]],
        ]))->assertSessionHasNoErrors();

        $quote = Quote::with('items')->firstOrFail();
        $this->assertSame('28500.00', $quote->items->first()->unit_price);
        $this->assertSame('28500.00', $quote->subtotal);
        $this->assertSame('2850.00', $quote->discount_amount);
        $this->assertSame('28150.00', $quote->total);
    }

    public function test_revalida_disponibilidad_al_enviar(): void
    {
        $this->reserve($this->band, $this->date);

        $this->post('/cotizador', $this->payload())
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Quote::count());
        Mail::assertNothingSent();
    }

    public function test_validacion_de_campos_obligatorios_en_espanol(): void
    {
        $this->post('/cotizador', [])
            ->assertSessionHasErrors([
                'client_name' => 'Tu nombre completo es un dato obligatorio.',
                'client_email' => 'Tu correo electrónico es un dato obligatorio.',
                'client_phone' => 'Escribe tu teléfono o WhatsApp.',
                'event_date' => 'Elige la fecha de tu evento.',
                'event_type' => 'Elige el tipo de evento.',
                'coverage_zone_id' => 'Elige la ciudad o zona de tu evento.',
                'items' => 'Agrega al menos un servicio a tu cotización.',
                'accept_privacy' => 'Para enviarte tu cotización necesitamos que aceptes el aviso de privacidad.',
            ]);

        $this->post('/cotizador', $this->payload(['event_date' => '2026-01-01', 'event_type' => 'Inventado', 'client_email' => 'no-es-correo', 'client_phone' => '123']))
            ->assertSessionHasErrors(['event_date', 'event_type', 'client_email', 'client_phone']);

        $this->assertSame(0, Quote::count());
    }

    public function test_otra_ciudad_requiere_nombre_y_marca_viaticos_por_confirmar(): void
    {
        $custom = $this->zone(CoverageZone::TYPE_CUSTOM_QUOTE, overrides: ['city_name' => 'Otra ciudad']);

        $this->post('/cotizador', $this->payload(['coverage_zone_id' => $custom->id]))
            ->assertSessionHasErrors(['custom_city_name' => 'Escribe la ciudad donde será tu evento.']);

        $this->post('/cotizador', $this->payload(['coverage_zone_id' => $custom->id, 'custom_city_name' => 'Puebla']))
            ->assertSessionHasNoErrors();

        $quote = Quote::firstOrFail();
        $this->assertSame('Puebla', $quote->custom_city_name);
        $this->assertSame('0.00', $quote->travel_fee);
    }

    public function test_trampa_anti_spam_rechaza_bots(): void
    {
        $this->post('/cotizador', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Quote::count());
    }

    public function test_confirmacion_y_pdf_requieren_url_firmada(): void
    {
        $this->post('/cotizador', $this->payload());
        $quote = Quote::firstOrFail();

        // El folio por sí solo no da acceso a los datos del cliente.
        $this->get('/cotizacion/COT-2026-0001')->assertForbidden();
        $this->get('/cotizacion/COT-2026-0001/pdf')->assertForbidden();

        $this->get(QuoteLinks::confirmation($quote))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/QuoteConfirmation')
                ->where('quote.folio', 'COT-2026-0001')
                ->where('quote.discount_amount', '4400.00')
                ->where('quote.travel.city', 'CDMX')
                ->where('downloadUrl', QuoteLinks::download($quote)));

        $this->get(QuoteLinks::download($quote))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="COT-2026-0001.pdf"');
    }

    public function test_falla_de_correo_no_pierde_la_cotizacion(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $this->post('/cotizador', $this->payload())->assertRedirect();

        $this->assertSame(1, Quote::count());
        Storage::disk(UploadService::DISK)->assertExists(Quote::first()->pdf_path);
    }

    public function test_pdf_incluye_identidad_folio_y_vigencia(): void
    {
        $this->post('/cotizador', $this->payload());
        $quote = Quote::with(['items', 'zone'])->firstOrFail();

        $html = view('pdf.quote', [
            'quote' => $quote,
            'settings' => \App\Models\SiteSetting::current(),
            'logo' => null,
            'primary' => '#6D28D9', 'secondary' => '#0F172A', 'accent' => '#F59E0B',
            'travel' => \App\Services\QuotePdfService::travelSummary($quote),
        ])->render();

        $this->assertStringContainsString('COT-2026-0001', $html);
        $this->assertStringContainsString('Colectivo de Prueba', $html);
        $this->assertStringContainsString('2 de diciembre de 2026', $html);
        $this->assertStringContainsString('Ahorro por cotizar en línea', $html);
        $this->assertStringContainsString('Términos de prueba.', $html);
        $this->assertStringContainsString('$42,100.00', $html);
    }
}
