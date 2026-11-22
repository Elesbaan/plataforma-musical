<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Vista raíz cargada en la primera visita.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props compartidas globalmente con todas las páginas.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'siteSettings' => fn () => $this->publicSettings(),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    ...$request->user()->only(['id', 'name', 'email']),
                    'role' => $request->user()->role?->only(['name', 'slug']),
                ] : null,
                // Claves de permiso efectivas: el menú y las acciones se muestran según ellas.
                'permissions' => fn () => $request->user()?->permissionKeys() ?? [],
                // Minutos de inactividad antes de cerrar la sesión (temporizador del panel).
                'sessionLifetime' => fn () => $request->user() ? (int) config('session.lifetime') : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Configuración de marca expuesta al frontend (sin campos internos).
     *
     * @return array<string, mixed>
     */
    private function publicSettings(): array
    {
        $settings = SiteSetting::current();

        return [
            ...$settings->only([
                'brand_name', 'tagline', 'seo_description',
                'whatsapp_number', 'phone_number', 'contact_email', 'address_text',
                'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'spotify_url',
                'online_discount_active', 'online_discount_type', 'online_discount_value',
                'online_discount_min_amount', 'quote_validity_days',
                'hero_title', 'hero_subtitle', 'hero_video_url',
                'logo_light_url', 'logo_dark_url', 'favicon_url',
            ]),
            'color_primary' => $settings->safeColor('color_primary'),
            'color_secondary' => $settings->safeColor('color_secondary'),
            'color_accent' => $settings->safeColor('color_accent'),
        ];
    }
}
