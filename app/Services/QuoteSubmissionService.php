<?php

namespace App\Services;

use App\Mail\NewQuoteNotification;
use App\Mail\QuoteSentToClient;
use App\Models\CoverageZone;
use App\Models\Quote;
use App\Models\SiteSetting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Flujo completo del cotizador público: recálculo estricto en servidor,
 * folio secuencial, PDF y correos.
 */
class QuoteSubmissionService
{
    private const FOLIO_RETRIES = 3;

    public function __construct(
        private readonly QuoteCalculatorService $calculator,
        private readonly QuotePdfService $pdf,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Datos validados por StoreQuoteRequest.
     *
     * @throws \Illuminate\Validation\ValidationException Si un servicio ya no tiene cupo.
     */
    public function submit(array $data): Quote
    {
        $zone = CoverageZone::findOrFail($data['coverage_zone_id']);

        // Recalcula todo con precios reales y revalida la disponibilidad de la fecha.
        $breakdown = $this->calculator->calculate($data['items'], $zone, $data['event_date']);

        $quote = $this->createWithFolio($data, $zone, $breakdown);

        $quote->update(['pdf_path' => $this->pdf->generate($quote)]);

        $this->sendEmails($quote);

        return $quote;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $breakdown
     */
    private function createWithFolio(array $data, CoverageZone $zone, array $breakdown): Quote
    {
        $settings = SiteSetting::current();

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $zone, $breakdown, $settings) {
                    $quote = Quote::create([
                        'folio' => $this->nextFolio(),
                        'client_name' => trim($data['client_name']),
                        'client_email' => $data['client_email'],
                        'client_phone' => $data['client_phone'],
                        'event_date' => $data['event_date'],
                        'event_time' => $data['event_time'] ?? null,
                        'event_type' => $data['event_type'],
                        'venue_name' => $data['venue_name'] ?? null,
                        'coverage_zone_id' => $zone->id,
                        'custom_city_name' => $zone->zone_type === CoverageZone::TYPE_CUSTOM_QUOTE ? trim((string) ($data['custom_city_name'] ?? '')) : null,
                        'subtotal' => $breakdown['subtotal'],
                        'travel_fee' => $breakdown['travel_fee'],
                        'discount_amount' => $breakdown['discount_amount'],
                        'total' => $breakdown['total'],
                        'valid_until' => now()->addDays(max(1, (int) ($settings->quote_validity_days ?? 7)))->toDateString(),
                        'status' => 'new',
                    ]);

                    foreach ($breakdown['lines'] as $line) {
                        $quote->items()->create([
                            'service_id' => $line['service_id'],
                            'service_name_snapshot' => $line['name'],
                            'quantity' => $line['quantity'],
                            'unit_price' => $line['unit_price'],
                            'line_total' => $line['line_total'],
                        ]);
                    }

                    return $quote;
                });
            } catch (UniqueConstraintViolationException $e) {
                // Dos envíos simultáneos tomaron el mismo folio: se reintenta con el siguiente.
                if ($attempt >= self::FOLIO_RETRIES) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Siguiente folio del año en curso: COT-YYYY-NNNN.
     */
    private function nextFolio(): string
    {
        $prefix = config('quotes.folio_prefix').'-'.now()->year.'-';

        $last = Quote::query()
            ->where('folio', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('folio')
            ->map(fn (string $folio) => (int) substr($folio, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Un fallo de correo no debe perder la cotización: se registra y el cliente
     * conserva su descarga directa y el botón de WhatsApp.
     */
    private function sendEmails(Quote $quote): void
    {
        try {
            Mail::to($quote->client_email, $quote->client_name)->send(new QuoteSentToClient($quote));
        } catch (\Throwable $e) {
            Log::error("No se pudo enviar la cotización {$quote->folio} al cliente.", ['error' => $e->getMessage()]);
        }

        $agencyEmail = SiteSetting::current()->contact_email;
        if (! $agencyEmail) {
            return;
        }

        try {
            Mail::to($agencyEmail)->send(new NewQuoteNotification($quote));
        } catch (\Throwable $e) {
            Log::error("No se pudo notificar la cotización {$quote->folio} a la agencia.", ['error' => $e->getMessage()]);
        }
    }
}
