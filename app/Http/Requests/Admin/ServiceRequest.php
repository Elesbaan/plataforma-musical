<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de servicios. Quien solo tiene catalog.manage_own puede enviar
 * únicamente los campos de contenido; los campos comerciales están prohibidos.
 */
class ServiceRequest extends FormRequest
{
    /** Campos que solo puede cambiar quien tiene catalog.manage_all. */
    public const RESTRICTED_FIELDS = [
        'category_id', 'name', 'service_type', 'base_price', 'price_unit', 'extra_hour_price', 'is_featured', 'is_active', 'sort_order',
    ];

    public function authorize(): bool
    {
        // La autorización por servicio la aplica ServicePolicy en la ruta.
        return $this->user() !== null;
    }

    public function isRestricted(): bool
    {
        return ! $this->user()->can('managePricing', Service::class);
    }

    protected function prepareForValidation(): void
    {
        // Ignora renglones vacíos de la lista de características.
        $features = collect((array) $this->input('features', []))
            ->map(fn ($feature) => is_string($feature) ? trim($feature) : $feature)
            ->filter(fn ($feature) => $feature !== '' && $feature !== null)
            ->values()
            ->all();

        $this->merge(['features' => $features]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $content = [
            'short_description' => ['nullable', 'string', 'max:300'],
            'full_description' => ['nullable', 'string', 'max:10000'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'features' => ['array', 'max:30'],
            'features.*' => ['required', 'string', 'max:200'],
        ];

        if ($this->isRestricted()) {
            return [...$content, ...array_fill_keys(self::RESTRICTED_FIELDS, ['prohibited'])];
        }

        return [
            ...$content,
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'service_type' => ['required', Rule::in(Service::TYPES)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'price_unit' => ['required', 'string', 'max:60'],
            'extra_hour_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_capacity.min' => 'La capacidad diaria debe ser de al menos 1 evento por fecha.',
            'base_price.prohibited' => 'No tienes permiso para modificar el precio de este servicio.',
            'price_unit.prohibited' => 'No tienes permiso para modificar la unidad de precio.',
            'extra_hour_price.prohibited' => 'No tienes permiso para modificar la tarifa de hora extra.',
            'name.prohibited' => 'No tienes permiso para cambiar el nombre del servicio.',
            'category_id.prohibited' => 'No tienes permiso para cambiar la categoría.',
            'service_type.prohibited' => 'No tienes permiso para cambiar el tipo de servicio.',
            'is_active.prohibited' => 'No tienes permiso para publicar u ocultar el servicio.',
            'is_featured.prohibited' => 'No tienes permiso para destacar el servicio.',
            'sort_order.prohibited' => 'No tienes permiso para cambiar el orden del catálogo.',
        ];
    }
}
