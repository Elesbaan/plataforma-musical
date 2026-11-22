<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dashboard operativo con métricas según los permisos del usuario.
     */
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        return Inertia::render('Admin/Dashboard', [
            ...$dashboard->forUser($request->user()),
            'today' => today()->toDateString(),
        ]);
    }
}
