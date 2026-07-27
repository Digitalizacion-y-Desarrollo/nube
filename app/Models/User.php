<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'external_id',
        'department_id',
        'name',
        'last_name',
        'email',
        'active',
        'last_login_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('created_at');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('created_at');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'owner_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'owner_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function sharedFolders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_collaborators')
            ->withPivot('created_at');
    }

    public function sharedFiles(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_collaborators')
            ->withPivot('created_at');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('name', $permission);
        }

        return $this->permissions()
            ->where('name', $permission)
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $role);
        }

        return $this->roles()
            ->where('name', $role)
            ->exists();
    }
}
