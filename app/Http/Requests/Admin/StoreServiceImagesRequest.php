<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:'.config('media.max_images_per_upload')],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('media.max_image_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Elige al menos una foto.',
            'images.max' => 'Puedes subir hasta :max fotos a la vez.',
            'images.*.max' => 'Cada foto puede pesar hasta '.(int) (config('media.max_image_kb') / 1024).' MB.',
            'images.*.mimes' => 'Las fotos deben ser JPG, PNG o WebP.',
            'images.*.image' => 'Las fotos deben ser JPG, PNG o WebP.',
        ];
    }
}
