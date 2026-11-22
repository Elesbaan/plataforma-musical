<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\Event;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;

/**
 * Convierte una cotización en evento confirmado: crea el registro en `events`
 * con sus partidas en `event_services` (validando capacidad diaria con
 * AvailabilityService a través de AgendaService) y marca la cotización como
 * confirmada.
 */
class QuoteConversionService
{
    public function __construct(private readonly AgendaService $agenda) {}

    /**
     * Datos iniciales del evento a partir de la cotización (editables antes de confirmar).
     *
     * @return array<string, mixed>
     */
    public function prefill(Quote $quote): array
    {
        $quote->loadMissing(['items', 'zone']);
        $travel = QuotePdfService::travelSummary($quote);
        $place = $quote->venue_name ?: $travel['city'];

        return [
            'title' => trim($quote->event_type.($place ? " — {$place}" : '')),
            'event_date' => $quote->event_date->toDateString(),
            'start_time' => $quote->event_time ? substr($quote->event_time, 0, 5) : '',
            'end_time' => '',
            'city_name' => $travel['city'] ?? '',
            'venue_name' => $quote->venue_name ?? '',
            'client_name' => $quote->client_name,
            'client_phone' => $quote->client_phone,
            'agreed_total' => $quote->total,
            'deposit_paid' => 0,
            'status' => Event::STATUS_RESERVED,
            'internal_notes' => "Generado desde la cotización {$quote->folio}.",
            // Solo las partidas de servicio (las horas extra no ocupan capacidad).
            'services' => $quote->items
                ->where('line_type', QuoteItem::TYPE_SERVICE)
                ->whereNotNull('service_id')
                ->map(fn (QuoteItem $item) => ['service_id' => $item->service_id, 'quantity' => $item->quantity])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por EventRequest.
     *
     * @throws \Illuminate\Validation\ValidationException Si hay sobrecupo sin confirmar.
     */
    public function convert(Quote $quote, array $data, bool $allowOverbooking = false): Event
    {
        if ($quote->event()->exists()) {
            throw new ActionNotAllowedException("La cotización {$quote->folio} ya tiene un evento agendado.");
        }

        return DB::transaction(function () use ($quote, $data, $allowOverbooking) {
            $event = $this->agenda->save([...$data, 'quote_id' => $quote->id], null, $allowOverbooking);
            $quote->update(['status' => Quote::STATUS_CONFIRMED]);

            return $event;
        });
    }
}
