<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Mail\QuoteSentToClient;
use App\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

/**
 * Mini-CRM de cotizaciones: listado, detalle, seguimiento y reenvío.
 */
class QuoteAdminService
{
    private const PER_PAGE = 20;

    public function __construct(private readonly QuotePdfService $pdf) {}

    /**
     * @param  array{status?: ?string, from?: ?string, to?: ?string, zone?: ?string, search?: ?string}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Quote::query()
            ->with('zone:id,city_name')
            ->withExists('event')
            ->when(in_array($filters['status'] ?? null, Quote::STATUSES, true), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('event_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('event_date', '<=', $to))
            ->when($filters['zone'] ?? null, fn ($q, $zone) => $q->where('coverage_zone_id', $zone))
            ->when(trim((string) ($filters['search'] ?? '')), function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $digits = preg_replace('/\D/', '', $term);
                $q->where(function ($w) use ($like, $digits) {
                    $w->where('folio', 'like', $like)->orWhere('client_name', 'like', $like)->orWhere('client_email', 'like', $like);
                    if (strlen($digits) >= 4) {
                        $w->orWhere('client_phone', 'like', "%{$digits}%");
                    }
                });
            })
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Quote $quote) => [
                ...$quote->only(['id', 'folio', 'client_name', 'client_phone', 'event_type', 'status', 'total']),
                'event_date' => $quote->event_date->toDateString(),
                'created_at' => $quote->created_at?->toDateTimeString(),
                'city' => $quote->custom_city_name ?: $quote->zone?->city_name,
                'has_event' => (bool) $quote->event_exists,
            ]);
    }

    /**
     * Conteo por estatus para las pestañas del listado.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = Quote::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(Quote::STATUSES)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Quote $quote): array
    {
        $quote->load(['items', 'zone', 'event:id,quote_id,title,event_date,status']);

        return [
            ...$quote->only(['id', 'folio', 'client_name', 'client_email', 'client_phone', 'event_type', 'venue_name', 'status', 'admin_notes', 'subtotal', 'travel_fee', 'discount_amount', 'total']),
            'event_date' => $quote->event_date->toDateString(),
            'event_time' => $quote->event_time ? substr($quote->event_time, 0, 5) : null,
            'valid_until' => $quote->valid_until->toDateString(),
            'created_at' => $quote->created_at?->toDateTimeString(),
            'is_expired' => $quote->valid_until->isPast() && ! $quote->valid_until->isToday(),
            'travel' => QuotePdfService::travelSummary($quote),
            'zone' => $quote->zone?->only(['id', 'city_name', 'zone_type']),
            'items' => $quote->items->map->only(['id', 'service_id', 'service_name_snapshot', 'line_type', 'quantity', 'unit_price', 'line_total']),
            'event' => $quote->event ? [
                'id' => $quote->event->id,
                'title' => $quote->event->title,
                'event_date' => $quote->event->event_date->toDateString(),
                'status' => $quote->event->status,
            ] : null,
        ];
    }

    /**
     * Cambia el estatus de seguimiento. "Confirmada" solo se asigna al convertir
     * la cotización en evento (QuoteConversionService).
     */
    public function updateStatus(Quote $quote, string $status): void
    {
        if ($status === Quote::STATUS_CONFIRMED && ! $quote->event()->exists()) {
            throw new ActionNotAllowedException('Para marcarla como confirmada, usa “Confirmar y agendar evento”.');
        }

        $quote->update(['status' => $status]);
    }

    public function updateNotes(Quote $quote, ?string $notes): void
    {
        $quote->update(['admin_notes' => $notes]);
    }

    /**
     * Reenvía la cotización al cliente con el PDF adjunto (lo regenera si falta).
     */
    public function resend(Quote $quote): void
    {
        $this->pdf->contents($quote);

        Mail::to($quote->client_email, $quote->client_name)->send(new QuoteSentToClient($quote->fresh()));
    }
}
