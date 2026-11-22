<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Administración de usuarios del panel.
 *
 * Salvaguardas: nadie puede desactivarse, cambiar su propio perfil ni eliminarse,
 * y siempre debe quedar al menos un Super Administrador activo.
 */
class UserAdminService
{
    private const PER_PAGE = 15;

    /**
     * @param  array{search?: ?string, role?: ?string, status?: ?string}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('role:id,name,slug')
            ->withCount('services')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role_id', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => $query,
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Servicios disponibles para vincular (alcance de permisos `*_own`).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function serviceOptions()
    {
        return Service::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'is_active'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category?->name,
                'is_active' => $service->is_active,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por UserRequest.
     */
    public function save(array $data, User $actor, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $actor, $user) {
            $user ??= new User;
            $role = Role::with('permissions:id,key')->findOrFail($data['role_id']);
            $isActive = (bool) ($data['is_active'] ?? false);

            if ($user->exists) {
                $this->guardChanges($user, $actor, $role, $isActive);
            }

            $user->fill([
                'name' => trim($data['name']),
                'email' => $data['email'],
                'role_id' => $role->id,
                'is_active' => $isActive,
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password']; // cast "hashed"
            }

            $user->save();

            // Los servicios vinculados solo aplican a perfiles con alcance propio.
            $hasOwnScope = $role->permissions->contains(fn (Permission $p) => Permission::isOwnScope($p->key));
            $user->services()->sync($hasOwnScope ? ($data['service_ids'] ?? []) : []);

            $user->flushPermissionCache();

            return $user;
        });
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new ActionNotAllowedException('No puedes eliminar tu propia cuenta.');
        }

        if ($this->isLastActiveSuperAdmin($user)) {
            throw new ActionNotAllowedException('Debe existir al menos un Super Administrador activo.');
        }

        $user->delete();
    }

    private function guardChanges(User $user, User $actor, Role $newRole, bool $isActive): void
    {
        if ($user->is($actor)) {
            if (! $isActive) {
                throw new ActionNotAllowedException('No puedes desactivar tu propia cuenta.');
            }
            if ($user->role_id !== $newRole->id) {
                throw new ActionNotAllowedException('No puedes cambiar tu propio perfil. Pide a otro administrador que lo haga.');
            }
        }

        $losesSuperAdmin = ! $newRole->isSuperAdmin() || ! $isActive;
        if ($losesSuperAdmin && $this->isLastActiveSuperAdmin($user)) {
            throw new ActionNotAllowedException('Debe existir al menos un Super Administrador activo.');
        }
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->is_active || ! $user->isSuperAdmin()) {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))
            ->doesntExist();
    }
}
