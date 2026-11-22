<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Punto de entrada del panel. Las métricas completas se integran en la Fase 5.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard');
    }
}
