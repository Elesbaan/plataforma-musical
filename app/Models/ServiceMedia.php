<?php

namespace App\Models;

use App\Services\UploadService;
use Illuminate\Database\Eloquent\Model;

class ServiceMedia extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO_LOCAL = 'video_local';

    public const TYPE_VIDEO_EMBED = 'video_embed';

    protected $guarded = [];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean', 'sort_order' => 'integer'];
    }

    public function service() { return $this->belongsTo(Service::class); }

    /** URL lista para mostrar: archivo en public/uploads o enlace de inserción. */
    public function getUrlAttribute(): ?string
    {
        return $this->media_type === self::TYPE_VIDEO_EMBED
            ? $this->embed_url
            : UploadService::url($this->file_path);
    }
}
