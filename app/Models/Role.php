<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';

    public const SALES_COORDINATOR = 'coordinador-ventas';

    public const PARTNER = 'proveedor-aliado';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }
}
