<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->is_admin) {
            return true;
        }

        // Papel admin do tenant tem acesso total
        if ($this->hasRoleSlug('admin')) {
            return true;
        }

        if ($this->permissions()->where('slug', $slug)->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->exists();
    }

    public function hasRoleSlug(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }
}