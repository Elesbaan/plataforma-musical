<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    public const TYPE_SERVICE = 'service';

    public const TYPE_EXTRA_HOURS = 'extra_hours';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'quantity' => 'integer'];
    }

    public function quote() { return $this->belongsTo(Quote::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
