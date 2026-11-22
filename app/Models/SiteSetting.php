<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    public const CACHE_KEY = 'site_settings';

    /** Valores por defecto de la paleta según el esquema (sección 6 del spec). */
    public const DEFAULT_COLORS = [
        'color_primary' => '#6D28D9',
        'color_secondary' => '#0F172A',
        'color_accent' => '#F59E0B',
    ];

    protected $guarded = [];

    protected $appends = ['logo_light_url', 'logo_dark_url', 'favicon_url'];

    protected function casts(): array
    {
        return [
            'online_discount_active' => 'boolean',
            'online_discount_value' => 'decimal:2',
            'online_discount_min_amount' => 'decimal:2',
            'quote_validity_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Configuración global vigente (cacheada). Si aún no existe registro,
     * devuelve una instancia vacía con los colores por defecto.
     */
    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->first() ?? new static(self::DEFAULT_COLORS);
        });
    }

    /**
     * Devuelve un color hexadecimal válido (#RRGGBB) para inyectarlo en CSS,
     * evitando que un valor malformado rompa o inyecte estilos.
     */
    public function safeColor(string $field): string
    {
        $value = (string) ($this->getAttribute($field) ?? '');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value)
            ? strtoupper($value)
            : self::DEFAULT_COLORS[$field];
    }

    /** Canales RGB ("R G B") para usar con opacidades de Tailwind. */
    public function rgbChannels(string $field): string
    {
        [$r, $g, $b] = sscanf($this->safeColor($field), '#%02x%02x%02x');

        return "{$r} {$g} {$b}";
    }

    public function getLogoLightUrlAttribute(): ?string
    {
        return $this->publicUrl($this->logo_light_path);
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->publicUrl($this->logo_dark_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->publicUrl($this->favicon_path);
    }

    private function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
