<?php

namespace App\Services;

use App\Models\CoverageZone;
use App\Models\Quote;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Genera y almacena el PDF formal de una cotización con la identidad de SiteSetting.
 *
 * Los archivos viven en public/uploads/quotes con nombre aleatorio y el acceso
 * directo está bloqueado por .htaccess: se descargan solo por URL firmada.
 */
class QuotePdfService
{
    public function __construct(private readonly UploadService $uploads) {}

    /**
     * Genera el PDF, lo guarda y devuelve su ruta relativa en el disco `uploads`.
     */
    public function generate(Quote $quote): string
    {
        $path = UploadService::DIR_QUOTES.'/'.$quote->folio.'-'.Str::random(24).'.pdf';

        $this->uploads->disk()->put($path, $this->render($quote));

        return $path;
    }

    /**
     * Contenido del PDF guardado (lo regenera si el archivo ya no existe).
     */
    public function contents(Quote $quote): string
    {
        if ($quote->pdf_path && $this->uploads->disk()->exists($quote->pdf_path)) {
            return $this->uploads->disk()->get($quote->pdf_path);
        }

        $quote->update(['pdf_path' => $this->generate($quote)]);

        return $this->uploads->disk()->get($quote->pdf_path);
    }

    public function render(Quote $quote): string
    {
        $quote->loadMissing(['items', 'zone']);
        $settings = SiteSetting::current();

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'settings' => $settings,
            'logo' => $this->logoDataUri($settings),
            'primary' => $settings->safeColor('color_primary'),
            'secondary' => $settings->safeColor('color_secondary'),
            'accent' => $settings->safeColor('color_accent'),
            'travel' => self::travelSummary($quote),
        ])
            ->setPaper('letter')
            ->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isFontSubsettingEnabled' => true])
            ->output();
    }

    /**
     * Ciudad y leyenda de viáticos para mostrar en PDF, correo y confirmación.
     *
     * @return array{city: ?string, pending: bool, label: ?string}
     */
    public static function travelSummary(Quote $quote): array
    {
        $zone = $quote->zone;
        $pending = $zone?->zone_type === CoverageZone::TYPE_CUSTOM_QUOTE;

        return [
            'city' => $quote->custom_city_name ?: $zone?->city_name,
            'pending' => $pending,
            'label' => match (true) {
                $pending => $zone->notes ?: config('quotes.travel_pending_label'),
                $zone?->zone_type === CoverageZone::TYPE_LOCAL_HQ => 'Sin costo de traslado',
                default => null,
            },
        ];
    }

    /**
     * Logo para fondo claro como data URI (DomPDF no carga archivos remotos y no lee WebP).
     */
    private function logoDataUri(SiteSetting $settings): ?string
    {
        $path = $settings->logo_light_path ?: $settings->logo_dark_path;
        if (! $path || ! $this->uploads->disk()->exists($path)) {
            return null;
        }

        $contents = $this->uploads->disk()->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if ($mime === 'image/webp' && function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring($contents);
            if ($image === false) {
                return null;
            }
            imagesavealpha($image, true);
            ob_start();
            imagepng($image);
            $contents = ob_get_clean();
            imagedestroy($image);
            $mime = 'image/png';
        }

        return in_array($mime, ['image/png', 'image/jpeg'], true)
            ? 'data:'.$mime.';base64,'.base64_encode($contents)
            : null;
    }
}
