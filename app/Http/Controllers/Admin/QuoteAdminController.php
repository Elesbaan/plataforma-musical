<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoverageZone;
use App\Models\Quote;
use App\Services\QuoteAdminService;
use App\Services\QuotePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mini-CRM de cotizaciones (quotes.view para consultar, quotes.manage para atender).
 */
class QuoteAdminController extends Controller
{
    public function __construct(private readonly QuoteAdminService $quotes) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'from', 'to', 'zone', 'search']);

        return Inertia::render('Admin/Quotes/Index', [
            'quotes' => $this->quotes->paginate($filters),
            'statusCounts' => $this->quotes->statusCounts(),
            'zones' => CoverageZone::orderBy('sort_order')->orderBy('city_name')->get(['id', 'city_name']),
            'filters' => (object) $filters,
        ]);
    }

    public function show(Request $request, Quote $quote): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Quotes/Show', [
            'quote' => $this->quotes->detail($quote),
            'abilities' => [
                'manage' => $user->can('quotes.manage'),
                'convert' => $user->can('quotes.manage') && $user->can('events.manage'),
            ],
        ]);
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(
            ['status' => ['required', Rule::in(Quote::STATUSES)]],
            ['status.in' => 'Elige un estatus válido.']
        );

        $this->quotes->updateStatus($quote, $data['status']);

        return back()->with('success', 'Se actualizó el estatus de la cotización.');
    }

    public function updateNotes(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);

        $this->quotes->updateNotes($quote, $data['admin_notes'] ?? null);

        return back()->with('success', 'Se guardaron las notas internas.');
    }

    public function resend(Quote $quote): RedirectResponse
    {
        $this->quotes->resend($quote);

        return back()->with('success', "Se reenvió la cotización a {$quote->client_email}.");
    }

    public function download(Quote $quote, QuotePdfService $pdf): HttpResponse
    {
        return response($pdf->contents($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$quote->folio.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
