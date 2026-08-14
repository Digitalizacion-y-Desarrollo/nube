<?php

namespace App\Models;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\Concerns\HasCollaboratorPermissions;
use App\Models\Concerns\RecordsDeletedBy;
use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasCollaboratorPermissions, HasFactory, HasUuids, RecordsDeletedBy, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'owner_id',
        'department_id',
        'name',
        'visibility',
        'collaboration_scope',
        'path_cache',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => FileVisibility::class,
            'collaboration_scope' => CollaborationScope::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'folder_collaborators')
            ->withPivot([
                'can_view',
                'can_download',
                'can_rename',
                'can_move',
                'can_delete',
                'created_at',
            ]);
    }
}
