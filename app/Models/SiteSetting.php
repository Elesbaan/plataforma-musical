<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'online_discount_active' => 'boolean',
            'online_discount_value' => 'decimal:2',
            'online_discount_min_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('site_settings'));
        static::deleted(fn () => Cache::forget('site_settings'));
    }
}
