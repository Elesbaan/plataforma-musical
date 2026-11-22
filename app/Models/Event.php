<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** Solo los eventos reservados descuentan capacidad diaria. */
    public const STATUS_RESERVED = 'reserved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'agreed_total' => 'decimal:2', 'deposit_paid' => 'decimal:2'];
    }

    public function quote() { return $this->belongsTo(Quote::class); }
    public function services() { return $this->belongsToMany(Service::class, 'event_services')->withPivot('quantity')->withTimestamps(); }
}
