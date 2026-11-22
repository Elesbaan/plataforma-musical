<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Acceso al catálogo de servicios.
 *
 * - catalog.manage_all: control total (crear, editar todo, precios, eliminar).
 * - catalog.manage_own: solo sus servicios vinculados (service_user) y solo
 *   galería, descripciones, características y capacidad diaria.
 *
 * El Super Administrador pasa por Gate::before.
 */
class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('catalog.manage_all') || $user->hasPermission('catalog.manage_own');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.manage_all');
    }

    /** Editar contenido del servicio y su galería. */
    public function update(User $user, Service $service): bool
    {
        if ($user->hasPermission('catalog.manage_all')) {
            return true;
        }

        return $user->hasPermission('catalog.manage_own') && $user->ownsService($service->id);
    }

    /** Cambiar nombre, categoría, tipo, precio, unidad, publicación y orden. */
    public function managePricing(User $user): bool
    {
        return $user->hasPermission('catalog.manage_all');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasPermission('catalog.manage_all');
    }
}
