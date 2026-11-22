<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UploadVideoChunkRequest extends FormRequest
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
        $limits = app(MediaLimits::class);
        $extensions = implode('|', array_map('preg_quote', $limits->videoExtensions()));
        $chunkKb = (int) ceil($limits->chunkBytes() / 1024);

        return [
            // UUID generado en el navegador: también evita rutas maliciosas en disco.
            'upload_id' => ['required', 'uuid'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'file_name' => ['required', 'string', 'max:255', "regex:/\\.({$extensions})$/i"],
            'total_size' => ['required', 'integer', 'min:1', 'max:'.$limits->maxVideoBytes()],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file', 'max:'.$chunkKb],
        ];
    }

    /**
     * El número de fragmentos debe corresponder al tamaño del archivo.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $expected = (int) ceil($this->integer('total_size') / app(MediaLimits::class)->chunkBytes());

                if ($this->integer('total_chunks') !== $expected) {
                    $validator->errors()->add('total_chunks', 'La subida no coincide con el tamaño del video. Recarga la página e inténtalo de nuevo.');
                } elseif ($this->integer('chunk_index') >= $expected) {
                    $validator->errors()->add('chunk_index', 'Fragmento fuera de rango.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = (int) (app(MediaLimits::class)->maxVideoBytes() / 1024 / 1024);

        return [
            'file_name.regex' => 'Formato no permitido. Sube videos MP4, WebM o MOV.',
            'total_size.max' => "El video no puede pesar más de {$maxMb} MB.",
        ];
    }
}
