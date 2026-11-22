<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageZone extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['travel_fee_value' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function quotes() { return $this->hasMany(Quote::class); }
}
