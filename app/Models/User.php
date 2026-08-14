<?php

namespace App\Models;

use App\Services\Profile\InitialsAvatarGenerator;
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
            ->withPivot([
                'can_view',
                'can_download',
                'can_rename',
                'can_move',
                'can_delete',
                'created_at',
            ]);
    }

    public function sharedFiles(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_collaborators')
            ->withPivot([
                'can_view',
                'can_download',
                'can_rename',
                'can_move',
                'can_delete',
                'created_at',
            ]);
    }

    public function hasAvatar(): bool
    {
        return is_string($this->avatar_path) && $this->avatar_path !== '';
    }

    public function initials(): string
    {
        return InitialsAvatarGenerator::initials($this->name, $this->last_name);
    }

    /**
     * URL de la foto de perfil. Sin imagen cargada se usan las iniciales del
     * usuario. Los avatares subidos viven en el disco privado, así que se
     * sirven por controlador; el sufijo cambia al reemplazar la imagen para
     * invalidar cualquier copia previa del navegador.
     */
    public function avatarUrl(): string
    {
        if (! $this->hasAvatar()) {
            return InitialsAvatarGenerator::dataUri($this->name, $this->last_name);
        }

        return route('profile.avatar', [
            'v' => substr(hash('sha256', (string) $this->avatar_path), 0, 12),
        ]);
    }

    public function hasPermission(string $permission): bool
    {
        $permissionNames = match ($permission) {
            'nube.archivos.subir' => ['nube.archivos.subir', 'nube_mis_archivos_subir'],
            'nube.archivos.descargar' => ['nube.archivos.descargar', 'nube_mis_archivos_descargar'],
            'nube.archivos.eliminar' => ['nube.archivos.eliminar', 'nube_mis_archivos_eliminar'],
            'nube.archivos.publicar' => ['nube.archivos.publicar', 'nube_mis_archivos_publicar'],
            'nube_archivos_crear_carpeta' => ['nube_archivos_crear_carpeta', 'nube_mis_archivos_crear_carpeta'],
            default => [$permission],
        };

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains(
                fn (Permission $assigned): bool => in_array(
                    $assigned->name,
                    $permissionNames,
                    true,
                ),
            );
        }

        return $this->permissions()
            ->whereIn('name', $permissionNames)
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
