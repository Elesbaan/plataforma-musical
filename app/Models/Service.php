<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'daily_capacity' => 'integer', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function features() { return $this->hasMany(ServiceFeature::class)->orderBy('sort_order'); }
    public function media() { return $this->hasMany(ServiceMedia::class)->orderBy('sort_order'); }
}
