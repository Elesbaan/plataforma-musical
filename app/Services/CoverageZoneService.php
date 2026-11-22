<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\CoverageZone;
use Illuminate\Database\Eloquent\Collection;

/**
 * Administración de zonas de cobertura y sus viáticos.
 *
 * Reglas: la sede local (local_hq) y "otra ciudad" (custom_quote) siempre
 * tienen viáticos de $0; solo las zonas foráneas cobran un monto fijo o un
 * porcentaje configurable.
 */
class CoverageZoneService
{
    /**
     * @return Collection<int, CoverageZone>
     */
    public function list(): Collection
    {
        return CoverageZone::query()
            ->withCount('quotes')
            ->orderBy('sort_order')
            ->orderBy('city_name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por CoverageZoneRequest.
     */
    public function save(array $data, ?CoverageZone $zone = null): CoverageZone
    {
        $zone ??= new CoverageZone;

        $zone->fill($this->normalize($data))->save();

        return $zone;
    }

    public function delete(CoverageZone $zone): void
    {
        if ($zone->quotes()->exists()) {
            throw new ActionNotAllowedException(
                "La zona {$zone->city_name} tiene cotizaciones registradas. Desactívala en lugar de eliminarla para conservar el historial."
            );
        }

        $zone->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $chargesTravel = $data['zone_type'] === CoverageZone::TYPE_FORANEA;

        return [
            'city_name' => trim($data['city_name']),
            'state_name' => isset($data['state_name']) ? trim($data['state_name']) : null,
            'zone_type' => $data['zone_type'],
            'travel_fee_type' => $chargesTravel ? $data['travel_fee_type'] : 'fixed',
            'travel_fee_value' => $chargesTravel ? $data['travel_fee_value'] : 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
