<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\CategoryService;
use App\Services\MediaLimits;
use App\Services\ServiceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de servicios. La autorización por acción la aplica ServicePolicy
 * en las rutas (can:viewAny / create / update / delete).
 */
class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceCatalogService $catalog,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'status']);
        $user = $request->user();
        $canManageAll = $user->can('managePricing', Service::class);

        return Inertia::render('Admin/Services/Index', [
            // Con alcance propio solo se listan los servicios vinculados al usuario.
            'services' => $this->catalog->paginate($filters, $canManageAll ? null : $user),
            'categories' => $this->categories->options(),
            'filters' => (object) $filters,
            'abilities' => ['create' => $user->can('create', Service::class)],
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

    public function edit(Request $request, Service $service, MediaLimits $limits): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Services/Edit', [
            'service' => $this->catalog->forEdit($service)->load('category:id,name'),
            'categories' => $this->categories->options(),
            'mediaLimits' => $limits->toArray(),
            'abilities' => [
                'managePricing' => $user->can('managePricing', Service::class),
                'delete' => $user->can('delete', $service),
            ],
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $request->isRestricted()
            ? $this->catalog->updateContent($service, $request->validated())
            : $this->catalog->update($service, $request->validated());

        return back()->with('success', "Se guardaron los cambios de {$service->name}.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->catalog->delete($service);

        return redirect()->route('admin.services.index')->with('success', "Se eliminó {$service->name}.");
    }
}
