<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoverageZoneRequest;
use App\Models\CoverageZone;
use App\Services\CoverageZoneService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoverageZoneController extends Controller
{
    public function __construct(private readonly CoverageZoneService $zones) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Zones/Index', [
            'zones' => $this->zones->list(),
        ]);
    }

    public function store(CoverageZoneRequest $request): RedirectResponse
    {
        $zone = $this->zones->save($request->validated());

        return back()->with('success', "Se agregó la zona {$zone->city_name}.");
    }

    public function update(CoverageZoneRequest $request, CoverageZone $zone): RedirectResponse
    {
        $this->zones->save($request->validated(), $zone);

        return back()->with('success', "Se actualizó la zona {$zone->city_name}.");
    }

    public function destroy(CoverageZone $zone): RedirectResponse
    {
        $this->zones->delete($zone);

        return back()->with('success', "Se eliminó la zona {$zone->city_name}.");
    }
}
