<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => $this->roles->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => null,
            'permissionGroups' => $this->roles->permissionGroups(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = $this->roles->save($request->validated());

        return redirect()->route('admin.roles.index')->with('success', "Se creó el perfil {$role->name}.");
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => [
                ...$role->only(['id', 'name', 'slug', 'description', 'is_system']),
                'is_super_admin' => $role->isSuperAdmin(),
                'permissions' => $role->permissions()->pluck('key'),
            ],
            'permissionGroups' => $this->roles->permissionGroups(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->roles->save($request->validated(), $role);

        return redirect()->route('admin.roles.index')->with('success', "Se actualizó el perfil {$role->name}.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roles->delete($role);

        return redirect()->route('admin.roles.index')->with('success', "Se eliminó el perfil {$role->name}.");
    }
}
