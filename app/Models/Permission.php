<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /** Los permisos `*_own` limitan el alcance a los servicios vinculados al usuario. */
    public static function isOwnScope(string $key): bool
    {
        return str_ends_with($key, '_own');
    }
}
