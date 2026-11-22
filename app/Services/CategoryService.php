<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @return Collection<int, Category>
     */
    public function list(): Collection
    {
        return Category::query()
            ->withCount('services')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Opciones para selectores (id y nombre).
     *
     * @return Collection<int, Category>
     */
    public function options(): Collection
    {
        return Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    /**
     * El slug se genera al crear y se conserva al editar para no romper
     * enlaces públicos ni posicionamiento SEO.
     *
     * @param  array<string, mixed>  $data  Datos validados por CategoryRequest.
     */
    public function save(array $data, ?Category $category = null): Category
    {
        $category ??= new Category;

        $attributes = [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if (! $category->exists) {
            $attributes['slug'] = $this->slugs->unique(Category::class, $attributes['name']);
        }

        $category->fill($attributes)->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $count = $category->services()->count();

        if ($count > 0) {
            throw new ActionNotAllowedException(
                "La categoría {$category->name} tiene {$count} servicio(s). Muévelos a otra categoría o desactívala en lugar de eliminarla."
            );
        }

        $category->delete();
    }
}
