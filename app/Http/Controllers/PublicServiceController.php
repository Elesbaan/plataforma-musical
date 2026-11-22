<?php

namespace App\Http\Controllers;

use App\Services\PublicCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Explorador público del catálogo y ficha de cada servicio.
 */
class PublicServiceController extends Controller
{
    public function __construct(private readonly PublicCatalogService $catalog) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['categoria', 'q', 'orden', 'destacados']);

        return Inertia::render('Public/Services/Index', [
            'services' => $this->catalog->paginate($filters),
            'categories' => $this->catalog->categories(),
            'filters' => (object) $filters,
        ]);
    }

    public function show(string $slug): Response
    {
        $detail = $this->catalog->detail($slug);

        abort_if($detail === null, 404);

        return Inertia::render('Public/Services/Show', $detail);
    }
}
