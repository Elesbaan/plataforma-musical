<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function quote() { return $this->belongsTo(Quote::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
