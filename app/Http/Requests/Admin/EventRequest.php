<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización la aplica la ruta (EventPolicy o permisos de la conversión).
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'city_name' => ['required', 'string', 'max:120'],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'client_name' => ['required', 'string', 'max:120'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'agreed_total' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'deposit_paid' => ['nullable', 'numeric', 'min:0', 'lte:agreed_total'],
            'status' => ['required', Rule::in([Event::STATUS_RESERVED, Event::STATUS_COMPLETED, Event::STATUS_CANCELLED])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'services' => ['required', 'array', 'min:1', 'max:30'],
            'services.*.service_id' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')],
            'services.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'force_overbooking' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'deposit_paid.lte' => 'El anticipo no puede ser mayor al monto acordado.',
            'services.required' => 'Agrega al menos un servicio al evento.',
            'services.min' => 'Agrega al menos un servicio al evento.',
            'services.*.service_id.distinct' => 'Un servicio aparece más de una vez.',
            'services.*.service_id.required' => 'Elige el servicio.',
        ];
    }
}
