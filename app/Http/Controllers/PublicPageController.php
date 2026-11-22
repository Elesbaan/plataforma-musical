<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    /**
     * Página de inicio. El contenido comercial completo se construye en la Fase 4.
     */
    public function home(): Response
    {
        return Inertia::render('Public/Home');
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Privacy');
    }
}
