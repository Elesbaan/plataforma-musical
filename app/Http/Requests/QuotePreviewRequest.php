<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Vista previa del cálculo en el cotizador público (sin crear la cotización).
 */
class QuotePreviewRequest extends FormRequest
{
    use QuoteItemsRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            ...$this->zoneRules(false),
            'event_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->itemMessages(),
            'event_date.after_or_equal' => 'Elige una fecha a partir de hoy.',
        ];
    }
}
