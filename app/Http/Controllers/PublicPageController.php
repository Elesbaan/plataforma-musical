<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\PublicCatalogService;
use App\Support\VideoEmbed;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(PublicCatalogService $catalog): Response
    {
        return Inertia::render('Public/Home', [
            'featured' => $catalog->featuredByCategory(),
            'highlights' => $catalog->highlights(),
            'eventTypes' => config('quotes.event_types'),
            // Video de portada (YouTube/Vimeo) normalizado a una URL de inserción segura.
            'heroVideoEmbed' => VideoEmbed::normalize(SiteSetting::current()->hero_video_url),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Privacy');
    }
}
