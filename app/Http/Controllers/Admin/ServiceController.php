<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\CategoryService;
use App\Services\ServiceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceCatalogService $catalog,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'status']);

        return Inertia::render('Admin/Services/Index', [
            'services' => $this->catalog->paginate($filters),
            'categories' => $this->categories->options(),
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Services/Create', [
            'categories' => $this->categories->options(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = $this->catalog->create($request->validated());

        return redirect()
            ->route('admin.services.edit', $service)
            ->with('success', "Se creó {$service->name}. Ahora puedes agregar fotos y videos.");
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Admin/Services/Edit', [
            'service' => $this->catalog->forEdit($service),
            'categories' => $this->categories->options(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->catalog->update($service, $request->validated());

        return back()->with('success', "Se guardaron los cambios de {$service->name}.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->catalog->delete($service);

        return redirect()->route('admin.services.index')->with('success', "Se eliminó {$service->name}.");
    }
}
