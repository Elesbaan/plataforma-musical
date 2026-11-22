<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'agreed_total' => 'decimal:2', 'deposit_paid' => 'decimal:2'];
    }

    public function quote() { return $this->belongsTo(Quote::class); }
    public function services() { return $this->belongsToMany(Service::class, 'event_services')->withPivot('quantity')->withTimestamps(); }
}
