<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageZone extends Model
{
    public const TYPE_LOCAL_HQ = 'local_hq';

    public const TYPE_FORANEA = 'foranea';

    public const TYPE_CUSTOM_QUOTE = 'custom_quote';

    public const TYPES = [self::TYPE_LOCAL_HQ, self::TYPE_FORANEA, self::TYPE_CUSTOM_QUOTE];

    public const FEE_TYPES = ['fixed', 'percentage'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['travel_fee_value' => 'decimal:2', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function quotes() { return $this->hasMany(Quote::class); }
}
