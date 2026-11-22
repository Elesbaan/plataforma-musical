<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingRequest extends FormRequest
{
    private const HEX_COLOR = 'regex:/^#[0-9A-Fa-f]{6}$/';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp_number' => $this->filled('whatsapp_number')
                ? preg_replace('/\D/', '', (string) $this->input('whatsapp_number'))
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $url = ['nullable', 'url:http,https', 'max:255'];
        $logo = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'];

        return [
            // Marca y portada
            'brand_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'hero_title' => ['nullable', 'string', 'max:160'],
            'hero_subtitle' => ['nullable', 'string', 'max:300'],
            'hero_video_url' => $url,
            'logo_light' => $logo,
            'logo_dark' => $logo,
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'remove_logo_light' => ['boolean'],
            'remove_logo_dark' => ['boolean'],
            'remove_favicon' => ['boolean'],

            // Colores
            'color_primary' => ['required', self::HEX_COLOR],
            'color_secondary' => ['required', self::HEX_COLOR],
            'color_accent' => ['required', self::HEX_COLOR],

            // Contacto y redes
            'whatsapp_number' => ['nullable', 'regex:/^\d{10,15}$/'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'facebook_url' => $url,
            'instagram_url' => $url,
            'tiktok_url' => $url,
            'youtube_url' => $url,
            'spotify_url' => $url,

            // Reglas del cotizador
            'online_discount_active' => ['boolean'],
            'online_discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'online_discount_value' => [
                'required', 'numeric', 'min:0',
                $this->input('online_discount_type') === 'percentage' ? 'max:100' : 'max:99999999',
            ],
            'online_discount_min_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'quote_validity_days' => ['required', 'integer', 'min:1', 'max:90'],
            'quote_terms_text' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color_primary.regex' => 'Elige un color primario válido (formato #RRGGBB).',
            'color_secondary.regex' => 'Elige un color secundario válido (formato #RRGGBB).',
            'color_accent.regex' => 'Elige un color de acento válido (formato #RRGGBB).',
            'whatsapp_number.regex' => 'Escribe el WhatsApp con lada de país, solo números (ej. 527221234567).',
            'online_discount_value.max' => $this->input('online_discount_type') === 'percentage'
                ? 'Un descuento porcentual no puede ser mayor a 100.'
                : 'El valor del descuento es demasiado alto.',
        ];
    }
}
