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
    /**
     * Unidades reservadas por servicio en una fecha.
     *
     * @return Collection<int, int> service_id => unidades
     */
    public function reservedUnits(Carbon|string $date): Collection
    {
        return DB::table('event_services')
            ->join('events', 'events.id', '=', 'event_services.event_id')
            ->where('events.status', Event::STATUS_RESERVED)
            ->whereDate('events.event_date', Carbon::parse($date)->toDateString())
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
