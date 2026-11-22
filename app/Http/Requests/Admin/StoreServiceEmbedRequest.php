<?php

namespace App\Http\Requests\Admin;

use App\Support\VideoEmbed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreServiceEmbedRequest extends FormRequest
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
        return [
            'embed_url' => ['required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->has('embed_url') && VideoEmbed::normalize($this->input('embed_url')) === null) {
                    $validator->errors()->add('embed_url', 'Pega un enlace válido de YouTube o Vimeo.');
                }
            },
        ];
    }

    public function embedUrl(): string
    {
        return VideoEmbed::normalize($this->input('embed_url'));
    }
}
