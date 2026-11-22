<?php

namespace App\Http\Requests;

use App\Models\CoverageZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Envío del cotizador público (captura de lead). Los montos NO se reciben:
 * QuoteCalculatorService los recalcula con precios de la base de datos.
 */
class StoreQuoteRequest extends FormRequest
{
    use QuoteItemsRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_phone' => preg_replace('/\D/', '', (string) $this->input('client_phone')),
            'client_email' => mb_strtolower(trim((string) $this->input('client_email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            ...$this->zoneRules(true),
            'custom_city_name' => ['nullable', 'string', 'max:120'],
            'event_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'event_type' => ['required', 'string', Rule::in(config('quotes.event_types'))],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'client_name' => ['required', 'string', 'min:3', 'max:120'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_phone' => ['required', 'regex:/^\d{10,15}$/'],
            'accept_privacy' => ['accepted'],
            // Trampa anti-spam: los humanos no ven este campo.
            'website' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $zone = CoverageZone::find($this->integer('coverage_zone_id'));
                if ($zone?->zone_type === CoverageZone::TYPE_CUSTOM_QUOTE && blank($this->input('custom_city_name'))) {
                    $validator->errors()->add('custom_city_name', 'Escribe la ciudad donde será tu evento.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->itemMessages(),
            'event_date.required' => 'Elige la fecha de tu evento.',
            'event_date.after_or_equal' => 'Elige una fecha a partir de hoy.',
            'event_type.required' => 'Elige el tipo de evento.',
            'event_type.in' => 'Elige un tipo de evento de la lista.',
            'event_time.date_format' => 'Escribe la hora en formato de 24 horas (ej. 18:30).',
            'client_phone.required' => 'Escribe tu teléfono o WhatsApp.',
            'client_phone.regex' => 'Escribe tu teléfono a 10 dígitos (puedes incluir lada de país).',
            'accept_privacy.accepted' => 'Para enviarte tu cotización necesitamos que aceptes el aviso de privacidad.',
            'website.prohibited' => 'No pudimos procesar tu solicitud. Intenta de nuevo.',
        ];
    }
}
