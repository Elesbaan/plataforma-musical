<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Agenda de eventos.
 *
 * - events.view_all: toda la agenda con datos financieros.
 * - events.view_own: solo eventos donde participa algún servicio vinculado al
 *   usuario (service_user), en modo lectura y sin datos financieros.
 * - events.manage: crear, editar y eliminar (implica ver toda la agenda).
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('events.view_all')
            || $user->hasPermission('events.view_own')
            || $user->hasPermission('events.manage');
    }

    public function view(User $user, Event $event): bool
    {
        if ($this->viewsAll($user)) {
            return true;
        }

        return $user->hasPermission('events.view_own')
            && $event->services()->whereIn('services.id', $user->services()->select('services.id'))->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermission('events.manage');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermission('events.manage');
    }

    /** Ver todos los eventos y sus montos (acordado, anticipo y saldo). */
    public function viewsAll(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('events.view_all') || $user->hasPermission('events.manage');
    }
}
