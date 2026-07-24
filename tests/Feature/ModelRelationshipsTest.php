<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_main_eloquent_relationships_can_operate_the_domain(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->for($department)->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $user->roles()->attach($role, ['created_at' => now()]);
        $user->permissions()->attach($permission, ['created_at' => now()]);

        $root = Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);
        $child = Folder::factory()->create([
            'parent_id' => $root->id,
            'owner_id' => $user->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);
        $file = File::factory()->create([
            'folder_id' => $child->id,
            'owner_id' => $user->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);
        $auditLog = AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'file.uploaded',
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'details' => ['name' => $file->display_name],
        ]);

        $this->assertTrue(Str::isUuid($root->id));
        $this->assertTrue(Str::isUuid($file->id));
        $this->assertTrue($user->department->is($department));
        $this->assertTrue($department->users->contains($user));
        $this->assertTrue($user->roles->contains($role));
        $this->assertTrue($user->permissions->contains($permission));
        $this->assertTrue($root->children->contains($child));
        $this->assertTrue($child->parent->is($root));
        $this->assertTrue($child->files->contains($file));
        $this->assertTrue($file->folder->is($child));
        $this->assertTrue($file->owner->is($user));
        $this->assertTrue($file->department->is($department));
        $this->assertTrue($user->auditLogs->contains($auditLog));
        $this->assertSame(FileVisibility::Private, $file->visibility);
        $this->assertSame(['name' => $file->display_name], $auditLog->details);
    }

    public function test_folders_and_files_use_soft_deletes(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->for($department)->create();
        $folder = Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $department->id,
        ]);
        $file = File::factory()->create([
            'folder_id' => $folder->id,
            'owner_id' => $user->id,
            'department_id' => $department->id,
        ]);

        $folder->delete();
        $file->delete();

        $this->assertSoftDeleted($folder);
        $this->assertSoftDeleted($file);
        $this->assertNotNull(Folder::withTrashed()->findOrFail($folder->id)->deleted_at);
        $this->assertNotNull(File::withTrashed()->findOrFail($file->id)->deleted_at);
    }

    public function test_department_parent_is_set_to_null_when_the_parent_is_deleted(): void
    {
        $parent = Department::factory()->create();
        $child = Department::factory()->create([
            'parent_id' => $parent->id,
            'parent_external_id' => $parent->external_id,
        ]);

        $parent->delete();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_role_and_permission_pivots_are_removed_with_the_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $user->roles()->attach($role, ['created_at' => now()]);
        $user->permissions()->attach($permission, ['created_at' => now()]);

        $user->delete();

        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $user->id]);
    }

    public function test_users_with_owned_folders_cannot_be_physically_deleted(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->for($department)->create();
        Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $department->id,
        ]);

        $this->expectException(QueryException::class);

        $user->delete();
    }

    public function test_audit_logs_keep_the_event_when_the_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);

        $user->delete();

        $this->assertNull($auditLog->fresh()->user_id);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'action' => 'auth.logout',
        ]);
    }
}
