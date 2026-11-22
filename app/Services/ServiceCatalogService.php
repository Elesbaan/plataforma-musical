<?php

namespace App\Services;

use App\Exceptions\ActionNotAllowedException;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Administración del catálogo de servicios y sus características incluidas.
 */
class ServiceCatalogService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly SlugService $slugs,
        private readonly UploadService $uploads,
    ) {}

    /**
     * @param  array{search?: ?string, category?: ?string, status?: ?string}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Service::query()
            ->with('category:id,name')
            ->withCount('media')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'featured' => $query->where('is_featured', true),
                default => $query,
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Servicio con todo lo necesario para editarlo.
     */
    public function forEdit(Service $service): Service
    {
        return $service->load(['features', 'media']);
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por ServiceRequest.
     */
    public function create(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            $service = Service::create([
                ...$this->attributes($data),
                'slug' => $this->slugs->unique(Service::class, $data['name']),
            ]);

            $this->syncFeatures($service, $data['features'] ?? []);

            return $service;
        });
    }

    /**
     * El slug se conserva al editar para no romper enlaces públicos.
     *
     * @param  array<string, mixed>  $data  Datos validados por ServiceRequest.
     */
    public function update(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            $service->update($this->attributes($data));
            $this->syncFeatures($service, $data['features'] ?? []);

            return $service;
        });
    }

    public function delete(Service $service): void
    {
        if ($service->events()->exists()) {
            throw new ActionNotAllowedException(
                "{$service->name} está asignado a eventos de la agenda. Desactívalo en lugar de eliminarlo para conservar el historial."
            );
        }

        $files = $service->media()->whereNotNull('file_path')->pluck('file_path')->all();

        $service->delete();

        array_map(fn ($path) => $this->uploads->delete($path), $files);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'category_id' => (int) $data['category_id'],
            'name' => trim($data['name']),
            'short_description' => $data['short_description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'service_type' => $data['service_type'],
            'base_price' => $data['base_price'],
            'price_unit' => trim($data['price_unit']),
            'daily_capacity' => (int) $data['daily_capacity'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /**
     * Reemplaza la lista de características respetando el orden recibido.
     *
     * @param  list<string>  $features
     */
    private function syncFeatures(Service $service, array $features): void
    {
        $service->features()->delete();

        foreach (array_values($features) as $index => $text) {
            $service->features()->create(['feature_text' => $text, 'sort_order' => $index + 1]);
        }
    }
}
