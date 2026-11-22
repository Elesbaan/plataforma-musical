<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Reglas compartidas para las partidas del cotizador (vista previa y envío).
 */
trait QuoteItemsRules
{
    /**
     * @return array<string, mixed>
     */
    protected function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:'.config('quotes.max_items')],
            'items.*.service_id' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('quotes.max_quantity')],
            'items.*.extra_hours' => ['nullable', 'integer', 'min:0', 'max:'.config('quotes.max_extra_hours')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function zoneRules(bool $required): array
    {
        return [
            'coverage_zone_id' => [$required ? 'required' : 'nullable', 'integer', Rule::exists('coverage_zones', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function itemMessages(): array
    {
        return [
            'items.required' => 'Agrega al menos un servicio a tu cotización.',
            'items.min' => 'Agrega al menos un servicio a tu cotización.',
            'items.*.service_id.exists' => 'Uno de los servicios ya no está disponible.',
            'items.*.service_id.distinct' => 'Un servicio aparece más de una vez.',
            'items.*.quantity.max' => 'La cantidad máxima por servicio es :max.',
            'items.*.extra_hours.max' => 'El máximo de horas extra es :max.',
            'coverage_zone_id.required' => 'Elige la ciudad o zona de tu evento.',
            'coverage_zone_id.exists' => 'La zona elegida ya no está disponible.',
        ];
    }
}
