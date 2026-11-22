<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use App\Policies\EventPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agenda operativa multi-evento con alcance por permisos:
 *
 * - Quien ve toda la agenda (events.view_all / events.manage) recibe todos los
 *   datos, incluidos montos acordados, anticipos y saldos.
 * - Con events.view_own solo se listan eventos donde participa algún servicio
 *   vinculado al usuario, sin datos del cliente ni financieros, y solo con sus
 *   propios servicios.
 */
class AgendaService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly EventPolicy $policy,
    ) {}

    public function viewsAll(User $user): bool
    {
        return $this->policy->viewsAll($user);
    }

    /**
     * Consulta de eventos visibles para el usuario.
     */
    public function visibleQuery(User $user): Builder
    {
        $query = Event::query()->with(['services:id,name', 'quote:id,folio']);

        if ($this->viewsAll($user)) {
            return $query;
        }

        if (! $user->hasPermission('events.view_own')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('services', fn ($q) => $q->whereIn('services.id', $this->ownServiceIds($user)));
    }

    /**
     * Eventos de un mes (vista de calendario).
     *
     * @return list<array<string, mixed>>
     */
    public function month(User $user, Carbon $month): array
    {
        return $this->visibleQuery($user)
            ->whereBetween('event_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()->endOfDay()])
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Event $event) => $this->present($event, $user))
            ->all();
    }

    /**
     * Próximos eventos reservados en orden cronológico (vista de lista).
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(User $user, int $limit = 50, ?int $days = null): array
    {
        return $this->visibleQuery($user)
            ->where('status', Event::STATUS_RESERVED)
            ->whereDate('event_date', '>=', today())
            ->when($days, fn ($q) => $q->whereDate('event_date', '<=', today()->addDays($days)))
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (Event $event) => $this->present($event, $user))
            ->all();
    }

    /**
     * Días del mes con servicios en su capacidad máxima o sobrevendidos.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function saturatedDays(User $user, Carbon $month): array
    {
        return $this->availability->saturatedDays(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
            $this->viewsAll($user) ? null : $this->ownServiceIds($user),
        );
    }

    /**
     * Datos de un evento según el alcance del usuario.
     *
     * @return array<string, mixed>
     */
    public function present(Event $event, User $user): array
    {
        $event->loadMissing(['services:id,name', 'quote:id,folio']);
        $full = $this->viewsAll($user);
        $own = $full ? null : $this->ownServiceIds($user);

        $data = [
            ...$event->only(['id', 'title', 'city_name', 'venue_name', 'status']),
            'event_date' => $event->event_date->toDateString(),
            'start_time' => $event->start_time ? substr($event->start_time, 0, 5) : null,
            'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
            'services' => $event->services
                ->filter(fn (Service $service) => $full || in_array($service->id, $own, true))
                ->map(fn (Service $service) => ['id' => $service->id, 'name' => $service->name, 'quantity' => (int) $service->pivot->quantity])
                ->values()
                ->all(),
            'is_full_view' => $full,
        ];

        if ($full) {
            $data += [
                'client_name' => $event->client_name,
                'client_phone' => $event->client_phone,
                'agreed_total' => $event->agreed_total,
                'deposit_paid' => $event->deposit_paid,
                'balance' => number_format(max(0, (float) $event->agreed_total - (float) $event->deposit_paid), 2, '.', ''),
                'internal_notes' => $event->internal_notes,
                'quote' => $event->quote?->only(['id', 'folio']),
            ];
        }

        return $data;
    }

    /**
     * Datos para el formulario de edición.
     *
     * @return array<string, mixed>
     */
    public function forEdit(Event $event): array
    {
        $event->load('services:id,name');

        return [
            ...$event->only(['id', 'title', 'city_name', 'venue_name', 'client_name', 'client_phone', 'agreed_total', 'deposit_paid', 'status', 'internal_notes', 'quote_id']),
            'event_date' => $event->event_date->toDateString(),
            'start_time' => $event->start_time ? substr($event->start_time, 0, 5) : '',
            'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : '',
            'services' => $event->services->map(fn (Service $service) => ['service_id' => $service->id, 'quantity' => (int) $service->pivot->quantity])->all(),
        ];
    }

    /**
     * Servicios disponibles para vincular a eventos.
     *
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function serviceOptions(?Event $event = null)
    {
        $linked = $event ? $event->services()->pluck('services.id')->all() : [];

        return Service::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $linked))
            ->orderBy('name')
            ->get(['id', 'name', 'daily_capacity', 'is_active']);
    }

    /**
     * Crea o actualiza un evento. Si algún servicio supera su capacidad diaria,
     * exige confirmación explícita ($allowOverbooking).
     *
     * @param  array<string, mixed>  $data  Datos validados por EventRequest.
     *
     * @throws ValidationException Si hay sobrecupo sin confirmar.
     */
    public function save(array $data, ?Event $event = null, bool $allowOverbooking = false): Event
    {
        $services = collect($data['services'])->mapWithKeys(fn ($row) => [(int) $row['service_id'] => (int) $row['quantity']])->all();
        $status = $data['status'] ?? Event::STATUS_RESERVED;

        if ($status === Event::STATUS_RESERVED && ! $allowOverbooking) {
            $this->guardOverbooking($data['event_date'], $services, $event?->id);
        }

        return DB::transaction(function () use ($data, $event, $services, $status) {
            $event ??= new Event;
            $event->fill([
                'quote_id' => $data['quote_id'] ?? $event->quote_id,
                'title' => trim($data['title']),
                'event_date' => $data['event_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'city_name' => trim($data['city_name']),
                'venue_name' => $data['venue_name'] ?? null,
                'client_name' => trim($data['client_name']),
                'client_phone' => $data['client_phone'] ?? null,
                'agreed_total' => $data['agreed_total'],
                'deposit_paid' => $data['deposit_paid'] ?? 0,
                'status' => $status,
                'internal_notes' => $data['internal_notes'] ?? null,
            ])->save();

            $event->services()->sync(collect($services)->map(fn ($quantity) => ['quantity' => $quantity])->all());

            return $event;
        });
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    /**
     * @param  array<int, int>  $services
     *
     * @throws ValidationException
     */
    public function guardOverbooking(string $date, array $services, ?int $excludeEventId = null): void
    {
        $over = collect($this->availability->conflicts($date, $services, $excludeEventId))
            ->where('level', AvailabilityService::LEVEL_OVER);

        if ($over->isEmpty()) {
            return;
        }

        $detail = $over->map(fn ($c) => "{$c['name']} ({$c['total']} de {$c['capacity']})")->implode(', ');

        throw ValidationException::withMessages([
            'overbooking' => "Sobrecupo en esta fecha: {$detail}. Ajusta los servicios o confirma bajo tu criterio.",
        ]);
    }

    /**
     * @return list<int>
     */
    private function ownServiceIds(User $user): array
    {
        return $user->services()->pluck('services.id')->map(fn ($id) => (int) $id)->all();
    }
}
