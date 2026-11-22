<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\CoverageZone;
use App\Models\Quote;
use App\Services\PublicCatalogService;
use App\Services\QuotePdfService;
use App\Services\QuoteSubmissionService;
use App\Support\QuoteLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cotizador público: armado, envío, confirmación y descarga del PDF.
 */
class QuoteController extends Controller
{
    public function create(Request $request, PublicCatalogService $catalog): Response
    {
        $eventTypes = config('quotes.event_types');

        return Inertia::render('Public/QuoteBuilder', [
            'categories' => $catalog->quotableServices(),
            'zones' => CoverageZone::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('city_name')
                ->get(['id', 'city_name', 'state_name', 'zone_type', 'travel_fee_type', 'travel_fee_value', 'notes']),
            'eventTypes' => $eventTypes,
            'preselect' => [
                'service' => $request->string('servicio')->toString() ?: null,
                'eventType' => in_array($request->input('tipo'), $eventTypes, true) ? $request->input('tipo') : null,
            ],
            'limits' => [
                'maxQuantity' => config('quotes.max_quantity'),
                'maxExtraHours' => config('quotes.max_extra_hours'),
            ],
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreQuoteRequest $request, QuoteSubmissionService $submission): RedirectResponse
    {
        $quote = $submission->submit($request->validated());

        return redirect()->to(QuoteLinks::confirmation($quote));
    }

    /**
     * Confirmación (URL firmada: el folio por sí solo no da acceso).
     */
    public function show(string $folio): Response
    {
        $quote = Quote::with(['items', 'zone'])->where('folio', $folio)->firstOrFail();

        return Inertia::render('Public/QuoteConfirmation', [
            'quote' => [
                ...$quote->only(['folio', 'client_name', 'client_email', 'event_type', 'venue_name', 'subtotal', 'travel_fee', 'discount_amount', 'total']),
                'event_date' => $quote->event_date->toDateString(),
                'event_time' => $quote->event_time ? substr($quote->event_time, 0, 5) : null,
                'valid_until' => $quote->valid_until->toDateString(),
                'travel' => QuotePdfService::travelSummary($quote),
                'items' => $quote->items->map->only(['service_name_snapshot', 'quantity', 'unit_price', 'line_total']),
            ],
            'downloadUrl' => QuoteLinks::download($quote),
        ]);
    }

    /**
     * Descarga del PDF (URL firmada).
     */
    public function download(string $folio, QuotePdfService $pdf): HttpResponse
    {
        $quote = Quote::where('folio', $folio)->firstOrFail();

        return response($pdf->contents($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$quote->folio.'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
