<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMedia extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function service() { return $this->belongsTo(Service::class); }
}
