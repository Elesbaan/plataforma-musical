<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var list<string>|null Claves de permiso memorizadas durante la petición. */
    private ?array $permissionKeysCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /** Servicios vinculados (alcance de los permisos `*_own`). */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_user');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    /**
     * Claves de permiso efectivas. El Super Administrador tiene todas.
     *
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->permissionKeysCache !== null) {
            return $this->permissionKeysCache;
        }

        if (! $this->is_active || ! $this->role) {
            return $this->permissionKeysCache = [];
        }

        return $this->permissionKeysCache = $this->isSuperAdmin()
            ? array_keys(config('permissions.permissions'))
            : $this->role->permissions()->pluck('key')->all();
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissionKeys(), true);
    }

    public function ownsService(int $serviceId): bool
    {
        return $this->services()->whereKey($serviceId)->exists();
    }

    /** Limpia la memoria de permisos (tras cambiar de perfil). */
    public function flushPermissionCache(): void
    {
        $this->permissionKeysCache = null;
        $this->unsetRelation('role');
    }
}
