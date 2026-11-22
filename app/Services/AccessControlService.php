<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Sincroniza el catálogo de permisos y los perfiles base desde config/permissions.php.
 * Es idempotente: se puede ejecutar en cada despliegue (`php artisan permissions:sync`).
 */
class AccessControlService
{
    public function syncCatalog(): void
    {
        DB::transaction(function () {
            $catalog = config('permissions.permissions');

            foreach ($catalog as $key => $definition) {
                Permission::updateOrCreate(['key' => $key], [
                    'module' => $definition['module'],
                    'label' => $definition['label'],
                ]);
            }

            // Permisos retirados del catálogo dejan de existir.
            Permission::whereNotIn('key', array_keys($catalog))->delete();

            foreach (config('permissions.roles') as $slug => $definition) {
                $role = Role::firstOrNew(['slug' => $slug]);
                $isNew = ! $role->exists;

                $role->fill([
                    'name' => $role->name ?? $definition['name'],
                    'description' => $role->description ?? $definition['description'],
                    'is_system' => true,
                ])->save();

                // El Super Administrador siempre conserva todos los permisos; los demás
                // perfiles base solo reciben sus permisos iniciales al crearse.
                if ($definition['permissions'] === '*') {
                    $role->permissions()->sync(Permission::pluck('id'));
                } elseif ($isNew) {
                    $role->permissions()->sync(Permission::whereIn('key', $definition['permissions'])->pluck('id'));
                }
            }
        });
    }
}
