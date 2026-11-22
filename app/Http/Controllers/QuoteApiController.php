<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotePreviewRequest;
use App\Models\CoverageZone;
use App\Services\AvailabilityService;
use App\Services\QuoteCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints JSON del cotizador público.
 */
class QuoteApiController extends Controller
{
    /**
     * GET /api/availability?date=YYYY-MM-DD
     */
    public function availability(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(
            ['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']],
            [
                'date.required' => 'Elige la fecha de tu evento.',
                'date.date_format' => 'La fecha no es válida.',
                'date.after_or_equal' => 'Elige una fecha a partir de hoy.',
            ]
        );

        return response()->json([
            'date' => $data['date'],
            'services' => collect($availability->forDate($data['date']))
                ->map(fn ($status, $id) => ['id' => $id, 'remaining' => $status['remaining'], 'available' => $status['available']])
                ->values(),
        ]);
    }

    /**
     * POST /api/cotizador/calcular — desglose calculado en el servidor.
     */
    public function preview(QuotePreviewRequest $request, QuoteCalculatorService $calculator): JsonResponse
    {
        $data = $request->validated();
        $zone = isset($data['coverage_zone_id']) ? CoverageZone::find($data['coverage_zone_id']) : null;

        return response()->json($calculator->calculate($data['items'], $zone, $data['event_date'] ?? null));
    }
}
