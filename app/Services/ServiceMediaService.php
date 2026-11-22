<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Galería de un servicio: fotos, videos locales y videos insertados.
 * Solo una foto puede ser portada; la primera foto subida lo es por defecto.
 */
class ServiceMediaService
{
    public function __construct(private readonly UploadService $uploads) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<ServiceMedia>
     */
    public function addImages(Service $service, array $files): array
    {
        $created = [];

        foreach ($files as $file) {
            $path = $this->uploads->storeOptimizedImage($file, UploadService::DIR_SERVICE_IMAGES);
            $created[] = $this->create($service, [
                'media_type' => ServiceMedia::TYPE_IMAGE,
                'file_path' => $path,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);
        }

        $this->ensureCover($service);

        return $created;
    }

    public function addEmbed(Service $service, string $embedUrl, ?string $title): ServiceMedia
    {
        return $this->create($service, [
            'media_type' => ServiceMedia::TYPE_VIDEO_EMBED,
            'embed_url' => $embedUrl,
            'title' => $title,
        ]);
    }

    public function addLocalVideo(Service $service, string $path, ?string $title): ServiceMedia
    {
        return $this->create($service, [
            'media_type' => ServiceMedia::TYPE_VIDEO_LOCAL,
            'file_path' => $path,
            'title' => $title,
        ]);
    }

    /**
     * @param  array{title?: ?string, is_cover?: bool, move?: string}  $data
     */
    public function update(ServiceMedia $media, array $data): void
    {
        DB::transaction(function () use ($media, $data) {
            if (array_key_exists('title', $data)) {
                $media->update(['title' => $data['title']]);
            }

            if (! empty($data['is_cover']) && $media->media_type === ServiceMedia::TYPE_IMAGE) {
                ServiceMedia::where('service_id', $media->service_id)->update(['is_cover' => false]);
                $media->update(['is_cover' => true]);
            }

            if (isset($data['move'])) {
                $this->move($media, $data['move'] === 'up' ? -1 : 1);
            }
        });
    }

    public function delete(ServiceMedia $media): void
    {
        $service = $media->service;
        $path = $media->file_path;

        $media->delete();
        $this->uploads->delete($path);

        $this->resequence($service);
        $this->ensureCover($service);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function create(Service $service, array $attributes): ServiceMedia
    {
        return $service->media()->create([
            ...$attributes,
            'is_cover' => false,
            'sort_order' => ((int) $service->media()->max('sort_order')) + 1,
        ]);
    }

    /** Intercambia la posición con el elemento vecino. */
    private function move(ServiceMedia $media, int $direction): void
    {
        $items = $this->resequence($media->service);
        $index = $items->search(fn (ServiceMedia $item) => $item->is($media));
        $neighbor = $items->get($index + $direction);

        if ($index === false || $neighbor === null) {
            return;
        }

        $current = $items[$index];
        [$currentOrder, $neighborOrder] = [$current->sort_order, $neighbor->sort_order];
        $current->update(['sort_order' => $neighborOrder]);
        $neighbor->update(['sort_order' => $currentOrder]);
    }

    /**
     * Reasigna órdenes consecutivos (1..n) y devuelve la lista ordenada.
     *
     * @return \Illuminate\Support\Collection<int, ServiceMedia>
     */
    private function resequence(Service $service)
    {
        $items = $service->media()->get()->values();

        $items->each(function (ServiceMedia $item, int $index) {
            if ($item->sort_order !== $index + 1) {
                $item->update(['sort_order' => $index + 1]);
            }
        });

        return $items;
    }

    /** Si no hay portada, la primera foto pasa a serlo. */
    private function ensureCover(Service $service): void
    {
        $images = $service->media()->where('media_type', ServiceMedia::TYPE_IMAGE);

        if ((clone $images)->where('is_cover', true)->exists()) {
            return;
        }

        $images->first()?->update(['is_cover' => true]);
    }
}
