<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceEmbedRequest;
use App\Http\Requests\Admin\StoreServiceImagesRequest;
use App\Http\Requests\Admin\UpdateServiceMediaRequest;
use App\Models\Service;
use App\Models\ServiceMedia;
use App\Services\ServiceMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ServiceMediaController extends Controller
{
    public function __construct(private readonly ServiceMediaService $media) {}

    public function storeImages(StoreServiceImagesRequest $request, Service $service): RedirectResponse
    {
        $created = $this->media->addImages($service, $request->file('images'));

        return back()->with('success', count($created) === 1 ? 'Se agregó la foto.' : 'Se agregaron '.count($created).' fotos.');
    }

    public function storeEmbed(StoreServiceEmbedRequest $request, Service $service): RedirectResponse
    {
        $this->media->addEmbed($service, $request->embedUrl(), $request->validated('title'));

        return back()->with('success', 'Se agregó el video.');
    }

    public function update(UpdateServiceMediaRequest $request, ServiceMedia $media): RedirectResponse
    {
        Gate::authorize('update', $media->service);

        $this->media->update($media, $request->validated());

        return back()->with('success', 'Se actualizó la galería.');
    }

    public function destroy(ServiceMedia $media): RedirectResponse
    {
        Gate::authorize('update', $media->service);

        $this->media->delete($media);

        return back()->with('success', 'Se eliminó el archivo de la galería.');
    }
}
