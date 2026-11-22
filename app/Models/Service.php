<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** Tipos válidos de servicio (enum `service_type`). */
    public const TYPES = ['core_band', 'ceremony', 'soloist', 'partner_act', 'addon_extra'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'daily_capacity' => 'integer', 'is_featured' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function features() { return $this->hasMany(ServiceFeature::class)->orderBy('sort_order'); }
    public function media() { return $this->hasMany(ServiceMedia::class)->orderBy('sort_order')->orderBy('id'); }
    public function events() { return $this->belongsToMany(Event::class, 'event_services')->withPivot('quantity')->withTimestamps(); }
}
