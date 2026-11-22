<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Administración de perfiles y su matriz de permisos.
 *
 * Reglas: los perfiles base (is_system) no se eliminan; el Super Administrador
 * siempre conserva todos los permisos; un perfil con usuarios no se elimina.
 */
class RoleService
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @return Collection<int, Role>
     */
    public function list(): Collection
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    /**
     * Perfiles para selectores, indicando si tienen permisos de alcance propio.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function options()
    {
        return Role::query()->with('permissions:id,key')->orderBy('name')->get()->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'has_own_scope' => $role->permissions->contains(fn (Permission $p) => Permission::isOwnScope($p->key)),
        ]);
    }

    /**
     * Permisos agrupados por módulo para la matriz.
     *
     * @return list<array{module: string, label: string, permissions: list<array{key: string, label: string}>}>
     */
    public function permissionGroups(): array
    {
        $modules = config('permissions.modules');
        $order = array_keys(config('permissions.permissions'));

        return Permission::all()
            ->sortBy(fn (Permission $p) => array_search($p->key, $order, true))
            ->groupBy('module')
            ->map(fn ($permissions, $module) => [
                'module' => $module,
                'label' => $modules[$module] ?? $module,
                'permissions' => $permissions->map(fn (Permission $p) => ['key' => $p->key, 'label' => $p->label])->values()->all(),
            ])
            ->sortBy(fn ($group) => array_search($group['module'], array_keys($modules), true))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por RoleRequest.
     */
    public function save(array $data, ?Role $role = null): Role
    {
        return DB::transaction(function () use ($data, $role) {
            $role ??= new Role(['is_system' => false]);

            $attributes = [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
            ];
            if (! $role->exists) {
                $attributes['slug'] = $this->slugs->unique(Role::class, $attributes['name']);
            }
            $role->fill($attributes)->save();

            $keys = $role->isSuperAdmin()
                ? Permission::pluck('key')->all() // siempre todos
                : ($data['permissions'] ?? []);

            $role->permissions()->sync(Permission::whereIn('key', $keys)->pluck('id'));

            return $role;
        });
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new ActionNotAllowedException("El perfil {$role->name} es un perfil base del sistema y no se puede eliminar.");
        }

        $users = $role->users()->count();
        if ($users > 0) {
            throw new ActionNotAllowedException("El perfil {$role->name} está asignado a {$users} usuario(s). Asígnales otro perfil antes de eliminarlo.");
        }

        $role->delete();
    }
}
