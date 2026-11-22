<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'full_description' => ['nullable', 'string', 'max:10000'],
            'service_type' => ['required', Rule::in(Service::TYPES)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'price_unit' => ['required', 'string', 'max:60'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'features' => ['array', 'max:30'],
            'features.*' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_capacity.min' => 'La capacidad diaria debe ser de al menos 1 evento por fecha.',
        ];
    }
}
