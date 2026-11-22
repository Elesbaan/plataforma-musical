<?php

namespace App\Http\Requests\Admin;

use App\Models\CoverageZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoverageZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isForanea = $this->input('zone_type') === CoverageZone::TYPE_FORANEA;
        $isPercentage = $this->input('travel_fee_type') === 'percentage';

        return [
            'city_name' => ['required', 'string', 'max:120'],
            'state_name' => ['nullable', 'string', 'max:120'],
            'zone_type' => ['required', Rule::in(CoverageZone::TYPES)],
            'travel_fee_type' => [Rule::requiredIf($isForanea), 'nullable', Rule::in(CoverageZone::FEE_TYPES)],
            'travel_fee_value' => [
                Rule::requiredIf($isForanea), 'nullable', 'numeric', 'min:0',
                $isForanea && $isPercentage ? 'max:100' : 'max:99999999',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'travel_fee_type.required' => 'Elige cómo se cobran los viáticos de esta zona foránea.',
            'travel_fee_value.required' => 'Indica el monto de viáticos de esta zona foránea.',
            'travel_fee_value.max' => $this->input('travel_fee_type') === 'percentage'
                ? 'Un porcentaje de viáticos no puede ser mayor a 100.'
                : 'El monto de viáticos es demasiado alto.',
        ];
    }
}
