<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza la identidad global (registro único de site_settings) y sus archivos.
 */
class SiteSettingService
{
    /** Campo de archivo => columna donde se guarda la ruta. */
    private const FILE_FIELDS = [
        'logo_light' => 'logo_light_path',
        'logo_dark' => 'logo_dark_path',
        'favicon' => 'favicon_path',
    ];

    public function __construct(private readonly UploadService $uploads) {}

    /**
     * Registro editable (lo crea con valores por defecto si aún no existe).
     */
    public function editable(): SiteSetting
    {
        return SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::DEFAULT_COLORS);
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por UpdateSiteSettingRequest.
     */
    public function update(array $data): SiteSetting
    {
        $settings = $this->editable();
        $toDelete = [];

        $attributes = collect($data)
            ->except([...array_keys(self::FILE_FIELDS), 'remove_logo_light', 'remove_logo_dark', 'remove_favicon'])
            ->all();

        $attributes['color_primary'] = strtoupper($attributes['color_primary']);
        $attributes['color_secondary'] = strtoupper($attributes['color_secondary']);
        $attributes['color_accent'] = strtoupper($attributes['color_accent']);
        $attributes['online_discount_active'] = (bool) ($data['online_discount_active'] ?? false);

        foreach (self::FILE_FIELDS as $field => $column) {
            $file = $data[$field] ?? null;
            $remove = (bool) ($data["remove_{$field}"] ?? false);

            if ($file instanceof UploadedFile) {
                $toDelete[] = $settings->{$column};
                $attributes[$column] = $this->uploads->store($file, UploadService::DIR_BRANDING);
            } elseif ($remove) {
                $toDelete[] = $settings->{$column};
                $attributes[$column] = null;
            }
        }

        DB::transaction(fn () => $settings->fill($attributes)->save());

        // Se borran los archivos anteriores solo cuando el cambio ya quedó guardado.
        array_map(fn ($path) => $this->uploads->delete($path), array_filter($toDelete));

        return $settings;
    }
}
