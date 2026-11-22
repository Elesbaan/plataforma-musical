<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Único responsable de calcular cupos ocupados vs daily_capacity por fecha.
 *
 * Regla: solo los eventos confirmados (events.status = 'reserved') descuentan
 * capacidad; las cotizaciones nunca bloquean una fecha.
 */
class AvailabilityService
{
    public const LEVEL_FULL = 'full';

    public const LEVEL_OVER = 'over';

    /**
     * Unidades reservadas por servicio en una fecha.
     *
     * @param  int|null  $excludeEventId  Evento que se está editando (no se cuenta contra sí mismo).
     * @return Collection<int, int> service_id => unidades
     */
    public function reservedUnits(Carbon|string $date, ?int $excludeEventId = null): Collection
    {
        return DB::table('event_services')
            ->join('events', 'events.id', '=', 'event_services.event_id')
            ->where('events.status', Event::STATUS_RESERVED)
            ->whereDate('events.event_date', Carbon::parse($date)->toDateString())
            ->when($excludeEventId, fn ($q) => $q->where('events.id', '!=', $excludeEventId))
            ->groupBy('event_services.service_id')
            ->selectRaw('event_services.service_id as service_id, SUM(event_services.quantity) as units')
            ->pluck('units', 'service_id')
            ->map(fn ($units) => (int) $units);
    }

    /**
     * Disponibilidad de todos los servicios activos en una fecha.
     *
     * @return array<int, array{capacity: int, reserved: int, remaining: int, available: bool}>
     */
    public function forDate(Carbon|string $date): array
    {
        $reserved = $this->reservedUnits($date);

        return Service::query()
            ->where('is_active', true)
            ->get(['id', 'daily_capacity'])
            ->mapWithKeys(fn (Service $service) => [$service->id => $this->status($service->daily_capacity, $reserved[$service->id] ?? 0)])
            ->all();
    }

    /**
     * Unidades libres de un servicio en una fecha.
     */
    public function remaining(Service $service, Carbon|string $date): int
    {
        $reserved = $this->reservedUnits($date)[$service->id] ?? 0;

        return $this->status($service->daily_capacity, $reserved)['remaining'];
    }

    /**
     * Servicios que alcanzarían (full) o superarían (over) su capacidad diaria
     * al agregar las cantidades indicadas en esa fecha.
     *
     * @param  array<int, int>  $requested  service_id => unidades
     * @return list<array{service_id: int, name: string, capacity: int, reserved: int, requested: int, total: int, level: string}>
     */
    public function conflicts(Carbon|string $date, array $requested, ?int $excludeEventId = null): array
    {
        if ($requested === []) {
            return [];
        }

        $reserved = $this->reservedUnits($date, $excludeEventId);

        return Service::query()
            ->whereIn('id', array_keys($requested))
            ->orderBy('name')
            ->get(['id', 'name', 'daily_capacity'])
            ->map(function (Service $service) use ($reserved, $requested) {
                $total = ($reserved[$service->id] ?? 0) + (int) $requested[$service->id];

                return [
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'capacity' => $service->daily_capacity,
                    'reserved' => $reserved[$service->id] ?? 0,
                    'requested' => (int) $requested[$service->id],
                    'total' => $total,
                    'level' => $total > $service->daily_capacity ? self::LEVEL_OVER : self::LEVEL_FULL,
                    'flag' => $total >= $service->daily_capacity,
                ];
            })
            ->filter(fn ($row) => $row['flag'])
            ->map(fn ($row) => collect($row)->except('flag')->all())
            ->values()
            ->all();
    }

    /**
     * Ocupación por fecha y servicio en un rango (para alertas del calendario).
     *
     * @param  list<int>|null  $onlyServiceIds  Limita a estos servicios (alcance propio).
     * @return array<string, list<array{service_id: int, name: string, capacity: int, reserved: int, level: string}>>
     */
    public function saturatedDays(Carbon $from, Carbon $to, ?array $onlyServiceIds = null): array
    {
        $rows = DB::table('event_services')
            ->join('events', 'events.id', '=', 'event_services.event_id')
            ->join('services', 'services.id', '=', 'event_services.service_id')
            ->where('events.status', Event::STATUS_RESERVED)
            ->whereBetween('events.event_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($onlyServiceIds !== null, fn ($q) => $q->whereIn('event_services.service_id', $onlyServiceIds))
            ->groupBy('events.event_date', 'event_services.service_id', 'services.name', 'services.daily_capacity')
            ->selectRaw('events.event_date as event_date, event_services.service_id as service_id, services.name as name, services.daily_capacity as capacity, SUM(event_services.quantity) as reserved')
            ->havingRaw('SUM(event_services.quantity) >= services.daily_capacity')
            ->get();

        $days = [];
        foreach ($rows as $row) {
            $days[Carbon::parse($row->event_date)->toDateString()][] = [
                'service_id' => (int) $row->service_id,
                'name' => $row->name,
                'capacity' => (int) $row->capacity,
                'reserved' => (int) $row->reserved,
                'level' => (int) $row->reserved > (int) $row->capacity ? self::LEVEL_OVER : self::LEVEL_FULL,
            ];
        }

        return $days;
    }

    /**
     * @return array{capacity: int, reserved: int, remaining: int, available: bool}
     */
    private function status(int $capacity, int $reserved): array
    {
        $remaining = max(0, $capacity - $reserved);

        return [
            'capacity' => $capacity,
            'reserved' => $reserved,
            'remaining' => $remaining,
            'available' => $remaining > 0,
        ];
    }
}
