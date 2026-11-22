<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Slug único para la tabla del modelo (agrega -2, -3... si ya existe).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function unique(string $modelClass, string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text) ?: 'elemento';
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
