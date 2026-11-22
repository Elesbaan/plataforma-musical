<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CoverageZone;
use App\Models\Service;
use App\Models\ServiceMedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consultas del sitio público: solo servicios activos de categorías activas.
 */
class PublicCatalogService
{
    private const PER_PAGE = 12;

    /** Opciones de ordenamiento del explorador. */
    public const SORTS = ['recomendados', 'precio-asc', 'precio-desc', 'nombre'];

    /**
     * Servicios destacados agrupados por categoría (portada).
     *
     * @return list<array{category: array<string, mixed>, services: list<array<string, mixed>>}>
     */
    public function featuredByCategory(): array
    {
        $services = $this->visible()
            ->where('is_featured', true)
            ->with($this->cardRelations())
            ->withExists($this->videoExists())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $services
            ->groupBy('category_id')
            ->sortBy(fn ($items) => [$items->first()->category->sort_order, $items->first()->category->name])
            ->map(fn ($items) => [
                'category' => $items->first()->category->only(['id', 'name', 'slug', 'icon', 'description']),
                'services' => $items->map(fn (Service $service) => $this->card($service))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Servicios cotizables agrupados por categoría (cotizador en línea).
     *
     * @return list<array{category: array<string, mixed>, services: list<array<string, mixed>>}>
     */
    public function quotableServices(): array
    {
        return $this->visible()
            ->with($this->cardRelations())
            ->withExists($this->videoExists())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category_id')
            ->sortBy(fn ($items) => [$items->first()->category->sort_order, $items->first()->category->name])
            ->map(fn ($items) => [
                'category' => $items->first()->category->only(['id', 'name', 'slug', 'icon']),
                'services' => $items->map(fn (Service $service) => [
                    ...$this->card($service),
                    'daily_capacity' => $service->daily_capacity,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Datos de la propuesta de valor (todo desde la base de datos).
     *
     * @return array<string, mixed>
     */
    public function highlights(): array
    {
        $visible = $this->visible();

        return [
            'base_city' => CoverageZone::where('is_active', true)->where('zone_type', CoverageZone::TYPE_LOCAL_HQ)->orderBy('sort_order')->value('city_name'),
            'zones_count' => CoverageZone::where('is_active', true)->where('zone_type', '!=', CoverageZone::TYPE_CUSTOM_QUOTE)->count(),
            'accepts_other_cities' => CoverageZone::where('is_active', true)->where('zone_type', CoverageZone::TYPE_CUSTOM_QUOTE)->exists(),
            'services_count' => (clone $visible)->count(),
            'own_count' => (clone $visible)->whereIn('service_type', ['core_band', 'ceremony', 'soloist'])->count(),
            'partner_count' => (clone $visible)->whereIn('service_type', ['partner_act', 'addon_extra'])->count(),
            'categories_count' => Category::where('is_active', true)->whereHas('services', fn ($q) => $q->where('is_active', true))->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas('services', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon'])
            ->toArray();
    }

    /**
     * @param  array{categoria?: ?string, q?: ?string, orden?: ?string, destacados?: mixed}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $sort = in_array($filters['orden'] ?? null, self::SORTS, true) ? $filters['orden'] : 'recomendados';

        $query = $this->visible()
            ->with($this->cardRelations())
            ->withExists($this->videoExists())
            ->when($filters['categoria'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when(! empty($filters['destacados']), fn ($q) => $q->where('is_featured', true))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhereHas('features', fn ($f) => $f->where('feature_text', 'like', $like)));
            });

        match ($sort) {
            'precio-asc' => $query->orderBy('base_price'),
            'precio-desc' => $query->orderByDesc('base_price'),
            'nombre' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name'),
        };

        return $query->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Service $service) => $this->card($service));
    }

    /**
     * Ficha completa de un servicio público, o null si no está publicado.
     *
     * @return array<string, mixed>|null
     */
    public function detail(string $slug): ?array
    {
        $service = $this->visible()
            ->where('slug', $slug)
            ->with(['category:id,name,slug,icon', 'features', 'media'])
            ->first();

        if (! $service) {
            return null;
        }

        $related = $this->visible()
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with($this->cardRelations())
            ->withExists($this->videoExists())
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (Service $item) => $this->card($item))
            ->all();

        return [
            'service' => [
                ...$this->card($service),
                'full_description' => $service->full_description,
                'daily_capacity' => $service->daily_capacity,
                'features' => $service->features->pluck('feature_text')->all(),
                'media' => $service->media
                    ->sortByDesc('is_cover')
                    ->values()
                    ->map(fn (ServiceMedia $media) => $media->only(['id', 'media_type', 'url', 'title']))
                    ->all(),
            ],
            'related' => $related,
        ];
    }

    /**
     * Datos de tarjeta para listados.
     *
     * @return array<string, mixed>
     */
    public function card(Service $service): array
    {
        $images = $service->media->where('media_type', ServiceMedia::TYPE_IMAGE);
        $cover = $images->firstWhere('is_cover', true) ?? $images->first();

        return [
            'id' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'short_description' => $service->short_description,
            'service_type' => $service->service_type,
            'base_price' => $service->base_price,
            'price_unit' => $service->price_unit,
            'extra_hour_price' => $service->extra_hour_price,
            'is_featured' => $service->is_featured,
            'cover_url' => $cover?->url,
            'has_video' => (bool) ($service->has_video ?? $service->media->whereIn('media_type', [ServiceMedia::TYPE_VIDEO_LOCAL, ServiceMedia::TYPE_VIDEO_EMBED])->isNotEmpty()),
            'category' => $service->category?->only(['id', 'name', 'slug', 'icon']),
        ];
    }

    private function visible(): Builder
    {
        return Service::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true));
    }

    /**
     * @return array<int|string, mixed>
     */
    private function cardRelations(): array
    {
        return [
            'category:id,name,slug,icon,description,sort_order',
            'media' => fn ($q) => $q->where('media_type', ServiceMedia::TYPE_IMAGE),
        ];
    }

    /**
     * @return array<string, \Closure>
     */
    private function videoExists(): array
    {
        return ['media as has_video' => fn ($q) => $q->whereIn('media_type', [ServiceMedia::TYPE_VIDEO_LOCAL, ServiceMedia::TYPE_VIDEO_EMBED])];
    }
}
