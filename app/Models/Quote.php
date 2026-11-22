<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'valid_until' => 'date', 'subtotal' => 'decimal:2', 'travel_fee' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function zone() { return $this->belongsTo(CoverageZone::class, 'coverage_zone_id'); }
    public function items() { return $this->hasMany(QuoteItem::class); }
    public function event() { return $this->hasOne(Event::class); }
}
