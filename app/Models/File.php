<?php

namespace App\Models;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'folder_id',
        'owner_id',
        'department_id',
        'original_name',
        'display_name',
        'stored_name',
        'disk',
        'path',
        'extension',
        'mime_type',
        'size_bytes',
        'visibility',
        'collaboration_scope',
        'checksum',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'visibility' => FileVisibility::class,
            'collaboration_scope' => CollaborationScope::class,
            'uploaded_at' => 'datetime',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'file_collaborators')
            ->withPivot('created_at');
    }
}
