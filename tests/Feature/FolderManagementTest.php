<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FolderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_navigate_nested_private_folders_with_breadcrumbs(): void
    {
        $user = User::factory()->create();
        $parent = $this->folder($user, 'Contratos', null, '/Contratos');
        $child = $this->folder($user, '2026', $parent, '/Contratos/2026');
        $this->folder($user, 'Julio', $child, '/Contratos/2026/Julio');
        $this->folder($user, 'Otra raíz', null, '/Otra raíz');

        $this->authenticated($user, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine.show', $child))
            ->assertOk()
            ->assertSee('Contratos')
            ->assertSee('2026')
            ->assertSee('Julio')
            ->assertSee('Ruta: /Contratos/2026')
            ->assertDontSee('Otra raíz');
    }

    public function test_user_cannot_open_another_users_private_folder_by_uuid(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignFolder = $this->folder($other, 'Privada ajena');

        $this->authenticated($user, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine.show', $foreignFolder))
            ->assertNotFound();
    }

    public function test_user_can_create_root_folders_and_subfolders_with_audit(): void
    {
        $user = User::factory()->create();
        $parent = $this->folder($user, 'Expedientes', null, '/Expedientes');

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => '  Personal  ',
                'parent_id' => $parent->id,
                'path_cache' => 'C:\\ruta\\inyectada',
            ])
            ->assertRedirect(route('folders.mine.show', $parent))
            ->assertSessionHas('status');

        $folder = Folder::query()->where('name', 'Personal')->firstOrFail();

        $this->assertSame($parent->id, $folder->parent_id);
        $this->assertSame($user->id, $folder->owner_id);
        $this->assertSame($user->department_id, $folder->department_id);
        $this->assertSame(FileVisibility::Private, $folder->visibility);
        $this->assertSame('/Expedientes/Personal', $folder->path_cache);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'folder.created',
            'resource_type' => Folder::class,
            'resource_id' => $folder->id,
        ]);
    }

    public function test_folder_name_is_required_valid_and_unique_within_the_same_level(): void
    {
        $user = User::factory()->create();
        $parent = $this->folder($user, 'Padre');
        $this->folder($user, 'Contratos', $parent);

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => 'contratos',
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('name', errorBag: 'createFolder');

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => 'Ruta/Inválida',
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('name', errorBag: 'createFolder');

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => Str::repeat('a', 151),
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('name', errorBag: 'createFolder');
    }

    public function test_user_cannot_create_inside_another_users_or_deleted_folder(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignFolder = $this->folder($other, 'Ajena');
        $deletedFolder = $this->folder($user, 'Eliminada');
        $deletedFolder->delete();

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => 'Intento',
                'parent_id' => $foreignFolder->id,
            ])
            ->assertForbidden();

        $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => 'Intento eliminado',
                'parent_id' => $deletedFolder->id,
            ])
            ->assertForbidden();
    }

    public function test_folder_visibility_can_differ_from_its_parent(): void
    {
        $user = User::factory()->create();
        $collaborative = $this->folder(
            $user,
            'Colaborativa',
            visibility: FileVisibility::Collaborative,
        );
        $public = $this->folder(
            $user,
            'Pública',
            visibility: FileVisibility::Public,
        );

        foreach ([$collaborative, $public] as $parent) {
            $this->authenticated($user, ['nube_mis_archivos_crear_carpeta'])
                ->post(route('folders.store'), [
                    'name' => "Subcarpeta privada {$parent->name}",
                    'parent_id' => $parent->id,
                ])
                ->assertRedirect()
                ->assertSessionHas('status');
        }

        $this->assertSame(
            2,
            Folder::query()
                ->where('visibility', FileVisibility::Private)
                ->whereNotNull('parent_id')
                ->count(),
        );
    }

    public function test_rename_updates_the_folder_and_all_descendant_logical_paths(): void
    {
        $user = User::factory()->create();
        $parent = $this->folder($user, 'Contratos', null, '/Contratos');
        $child = $this->folder($user, '2026', $parent, '/Contratos/2026');
        $grandchild = $this->folder($user, 'Julio', $child, '/Contratos/2026/Julio');

        $this->authenticated($user, ['nube_mis_archivos_renombrar'])
            ->patch(route('folders.update', $parent), [
                'name' => 'Convenios',
                'folder_context' => $parent->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('/Convenios', $parent->fresh()->path_cache);
        $this->assertSame('/Convenios/2026', $child->fresh()->path_cache);
        $this->assertSame('/Convenios/2026/Julio', $grandchild->fresh()->path_cache);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.renamed',
            'resource_id' => $parent->id,
        ]);
    }

    public function test_folder_cannot_be_renamed_to_a_duplicate_name_in_the_same_level(): void
    {
        $user = User::factory()->create();
        $first = $this->folder($user, 'Contratos');
        $second = $this->folder($user, 'Convenios');

        $this->authenticated($user, ['nube_mis_archivos_renombrar'])
            ->patch(route('folders.update', $second), [
                'name' => 'CONTRATOS',
                'folder_context' => $second->id,
            ])
            ->assertSessionHasErrors('name', errorBag: 'renameFolder');

        $this->assertSame('Convenios', $second->fresh()->name);
        $this->assertSame('Contratos', $first->fresh()->name);
    }

    public function test_user_cannot_rename_or_delete_another_users_folder(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignFolder = $this->folder($other, 'Ajena');

        $this->authenticated($user, [
            'nube_mis_archivos_renombrar',
            'nube_mis_archivos_eliminar',
        ])->patch(route('folders.update', $foreignFolder), [
            'name' => 'Manipulada',
            'folder_context' => $foreignFolder->id,
        ])->assertForbidden();

        $this->authenticated($user, [
            'nube_mis_archivos_renombrar',
            'nube_mis_archivos_eliminar',
        ])->delete(route('folders.destroy', $foreignFolder))
            ->assertForbidden();

        $this->assertSame('Ajena', $foreignFolder->fresh()->name);
        $this->assertFalse($foreignFolder->fresh()->trashed());
    }

    public function test_only_empty_folders_can_be_soft_deleted_and_deletion_is_audited(): void
    {
        $user = User::factory()->create();
        $notEmpty = $this->folder($user, 'Con contenido');
        $this->folder($user, 'Hija', $notEmpty);
        $empty = $this->folder($user, 'Vacía');

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('folders.destroy', $notEmpty))
            ->assertRedirect()
            ->assertSessionHas('folder_error');

        $this->assertFalse($notEmpty->fresh()->trashed());

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('folders.destroy', $empty))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($empty->fresh()->trashed());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.deleted',
            'resource_id' => $empty->id,
        ]);
    }

    public function test_folder_with_a_file_is_not_considered_empty(): void
    {
        $user = User::factory()->create();
        $folder = $this->folder($user, 'Con archivo');
        File::factory()->create([
            'folder_id' => $folder->id,
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'visibility' => FileVisibility::Private,
        ]);

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('folders.destroy', $folder))
            ->assertSessionHas('folder_error');

        $this->assertFalse($folder->fresh()->trashed());
        $this->assertSame(0, AuditLog::query()->where('action', 'folder.deleted')->count());
    }

    public function test_department_and_public_nested_folders_are_read_only_and_scoped(): void
    {
        $user = User::factory()->create();
        $sameDepartmentOwner = User::factory()->create([
            'department_id' => $user->department_id,
        ]);
        $departmentFolder = $this->folder(
            $sameDepartmentOwner,
            'Área compartida',
            visibility: FileVisibility::Collaborative,
        );
        $publicFolder = $this->folder(
            $sameDepartmentOwner,
            'Manual público',
            visibility: FileVisibility::Public,
        );

        $this->authenticated($user, ['nube_departamento_ver'])
            ->get(route('folders.department.show', $departmentFolder))
            ->assertOk()
            ->assertSee('Área compartida')
            ->assertDontSee('Nueva carpeta');

        $this->authenticated($user, ['nube_publicos_ver'])
            ->get(route('folders.public.show', $publicFolder))
            ->assertOk()
            ->assertSee('Manual público')
            ->assertDontSee('Nueva carpeta');
    }

    public function test_user_cannot_open_a_collaborative_folder_from_another_department(): void
    {
        $user = User::factory()->create();
        $outsideOwner = User::factory()->create();
        $outsideFolder = $this->folder(
            $outsideOwner,
            'Otra dirección',
            visibility: FileVisibility::Collaborative,
        );

        $this->authenticated($user, ['nube_departamento_ver'])
            ->get(route('folders.department.show', $outsideFolder))
            ->assertNotFound();
    }

    private function folder(
        User $owner,
        string $name,
        ?Folder $parent = null,
        ?string $path = null,
        FileVisibility $visibility = FileVisibility::Private,
    ): Folder {
        return Folder::factory()->create([
            'parent_id' => $parent?->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => $name,
            'visibility' => $visibility,
            'path_cache' => $path,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions): static
    {
        $allPermissions = array_values(array_unique([
            'nube_inicio_ver',
            ...$permissions,
        ]));

        foreach ($allPermissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );

            $user->permissions()->syncWithoutDetaching([
                $permission->id => ['created_at' => now()],
            ]);
        }

        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $allPermissions,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
