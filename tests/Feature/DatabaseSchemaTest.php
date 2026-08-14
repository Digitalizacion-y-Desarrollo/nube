<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_approved_domain_tables_are_created_from_an_empty_database(): void
    {
        foreach ([
            'departments',
            'users',
            'roles',
            'user_roles',
            'permissions',
            'user_permissions',
            'folders',
            'files',
            'audit_logs',
            'folder_collaborators',
            'file_collaborators',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }
    }

    public function test_users_follow_the_external_access_system_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'external_id',
            'department_id',
            'name',
            'last_name',
            'email',
            'active',
            'last_login_at',
            'last_synced_at',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse(Schema::hasColumn('users', 'password'));
        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
    }

    public function test_folders_files_and_audit_logs_have_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('folders', [
            'id',
            'parent_id',
            'owner_id',
            'department_id',
            'name',
            'visibility',
            'path_cache',
            'deleted_at',
            'deleted_by',
        ]));

        $this->assertTrue(Schema::hasColumns('files', [
            'id',
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
            'checksum',
            'uploaded_at',
            'deleted_at',
            'deleted_by',
        ]));

        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'id',
            'user_id',
            'action',
            'resource_type',
            'resource_id',
            'ip_address',
            'user_agent',
            'details',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));

        foreach (['folder_collaborators', 'file_collaborators'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'user_id',
                'can_view',
                'can_download',
                'can_rename',
                'can_move',
                'can_delete',
                'created_at',
            ]));
        }
    }
}
