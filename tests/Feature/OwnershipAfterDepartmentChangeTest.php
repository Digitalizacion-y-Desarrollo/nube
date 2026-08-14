<?php

namespace Tests\Feature;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnershipAfterDepartmentChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_keeps_private_resources_but_loses_old_department_resources(): void
    {
        Storage::fake('nube');

        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $oldDepartment->id]);
        $privateFolder = $this->folder($owner, FileVisibility::Private, 'Privada anterior');
        $collaborativeFolder = $this->folder(
            $owner,
            FileVisibility::Collaborative,
            'Colaborativa anterior',
        );
        $privateFile = $this->file($owner, FileVisibility::Private, 'Editar.pdf');
        $collaborativeFile = $this->file(
            $owner,
            FileVisibility::Collaborative,
            'Eliminar.pdf',
        );

        $owner->update(['department_id' => $newDepartment->id]);

        $this->authenticated($owner, [
            'nube_mis_archivos_ver',
            'nube_mis_archivos_renombrar',
            'nube_mis_archivos_eliminar',
            'nube_departamento_ver',
            'nube_departamento_renombrar',
            'nube_departamento_eliminar',
        ])->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('Privada anterior')
            ->assertSee('Editar.pdf');

        $this->get(route('folders.department'))
            ->assertOk()
            ->assertDontSee('Colaborativa anterior')
            ->assertDontSee('Eliminar.pdf');

        $this->patch(route('files.update', $privateFile), [
            'display_name' => 'Editado.pdf',
            'file_context' => $privateFile->id,
        ])->assertRedirect();

        $this->delete(route('files.destroy', $collaborativeFile))
            ->assertForbidden();

        $this->patch(route('folders.update', $privateFolder), [
            'name' => 'Privada editada',
            'folder_context' => $privateFolder->id,
        ])->assertRedirect();

        $this->delete(route('folders.destroy', $collaborativeFolder))
            ->assertForbidden();

        $this->assertSame('Editado.pdf', $privateFile->fresh()->display_name);
        $this->assertSame('Privada editada', $privateFolder->fresh()->name);
        $this->assertNotSoftDeleted($collaborativeFile);
        $this->assertNotSoftDeleted($collaborativeFolder);
        $this->assertSame($oldDepartment->id, $privateFile->fresh()->department_id);
        $this->assertSame($oldDepartment->id, $privateFolder->fresh()->department_id);
    }

    public function test_department_change_does_not_expose_old_collaborative_content_to_the_new_department(): void
    {
        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $oldDepartment->id]);
        $oldCoworker = User::factory()->create(['department_id' => $oldDepartment->id]);
        $newCoworker = User::factory()->create(['department_id' => $newDepartment->id]);
        $file = $this->file(
            $owner,
            FileVisibility::Collaborative,
            'Contenido del área anterior.pdf',
        );

        $owner->update(['department_id' => $newDepartment->id]);
        $this->grant($owner, ['nube_departamento_ver', 'nube_departamento_renombrar']);
        $this->grant($oldCoworker, ['nube_departamento_ver']);
        $this->grant($newCoworker, ['nube_departamento_ver']);

        $this->assertFalse($owner->can('view', $file));
        $this->assertFalse($owner->can('update', $file));
        $this->assertTrue($oldCoworker->can('view', $file));
        $this->assertFalse($newCoworker->can('view', $file));
    }

    public function test_area_admin_can_manage_collaborative_resources_from_their_department(): void
    {
        Storage::fake('nube');

        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $creator = User::factory()->create(['department_id' => $oldDepartment->id]);
        $areaAdmin = User::factory()->create(['department_id' => $oldDepartment->id]);
        $incorrectRoleUser = User::factory()->create([
            'department_id' => $oldDepartment->id,
        ]);
        $file = $this->file(
            $creator,
            FileVisibility::Collaborative,
            'Administrable.pdf',
        );
        $file->update(['collaboration_scope' => CollaborationScope::Selected]);
        $folder = $this->folder(
            $creator,
            FileVisibility::Collaborative,
            'Carpeta administrable',
        );
        $folder->update(['collaboration_scope' => CollaborationScope::Selected]);
        $creator->update(['department_id' => $newDepartment->id]);
        $this->grantRole($areaAdmin, 'admin_area');
        $this->grantRole($incorrectRoleUser, 'admin.area');
        $this->grant($incorrectRoleUser, [
            'nube_departamento_ver',
            'nube_departamento_renombrar',
            'nube_departamento_eliminar',
        ]);

        $this->assertFalse($incorrectRoleUser->can('update', $file));
        $this->assertFalse($incorrectRoleUser->can('delete', $file));

        $this->authenticated($areaAdmin, [
            'nube_departamento_ver',
            'nube_departamento_renombrar',
            'nube_departamento_eliminar',
        ])->get(route('folders.department'))
            ->assertOk()
            ->assertSee('Administrable.pdf')
            ->assertSee('Carpeta administrable');

        $this->patch(route('files.update', $file), [
            'display_name' => 'Administrado.pdf',
            'file_context' => $file->id,
        ])->assertRedirect();

        $this->delete(route('folders.destroy', $folder))
            ->assertRedirect();

        $this->delete(route('files.destroy', $file))
            ->assertRedirect();

        $this->assertSame('Administrado.pdf', $file->fresh()->display_name);
        $this->assertSoftDeleted($file);
        $this->assertSoftDeleted($folder);
    }

    public function test_moving_an_old_file_keeps_its_original_department_storage_boundary(): void
    {
        Storage::fake('nube');

        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $oldDepartment->id]);
        $folder = $this->folder($owner, FileVisibility::Private, 'Origen');
        $file = $this->file($owner, FileVisibility::Private, 'Mover.pdf', $folder);

        $owner->update(['department_id' => $newDepartment->id]);

        $this->authenticated($owner, ['nube_mis_archivos_mover'])
            ->patch(route('files.move', $file), [
                'destination_folder_id' => null,
                'file_context' => $file->id,
            ])
            ->assertRedirect();

        $file->refresh();

        $this->assertNull($file->folder_id);
        $this->assertSame($oldDepartment->id, $file->department_id);
        $this->assertStringContainsString(
            "departamentos/{$oldDepartment->id}/",
            $file->path,
        );
        $this->assertStringNotContainsString(
            "departamentos/{$newDepartment->id}/",
            $file->path,
        );
        Storage::disk('nube')->assertExists($file->path);
    }

    public function test_owner_can_add_private_content_to_an_owned_folder_from_a_previous_department(): void
    {
        Storage::fake('nube');

        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $oldDepartment->id]);
        $folder = $this->folder($owner, FileVisibility::Private, 'Privada anterior');

        $owner->update(['department_id' => $newDepartment->id]);

        $response = $this->authenticated($owner, [
            'nube_mis_archivos_ver',
            'nube_mis_archivos_subir',
            'nube_mis_archivos_crear_carpeta',
        ])->get(route('folders.mine.show', $folder));

        $response
            ->assertOk()
            ->assertSee('Agregar archivo')
            ->assertSee('Nueva subcarpeta')
            ->assertSee('name="folder_id"', false)
            ->assertSee('name="parent_id"', false);

        $this->assertSame(
            2,
            substr_count(
                $response->getContent(),
                'value="'.$folder->id.'" selected',
            ),
        );

        $this->post(route('folders.store'), [
            'name' => 'Nueva privada',
            'parent_id' => $folder->id,
            'visibility' => FileVisibility::Private->value,
        ])->assertRedirect(route('folders.mine.show', $folder));

        $this->post(route('files.store'), [
            'file' => UploadedFile::fake()->create(
                'nuevo.pdf',
                10,
                'application/pdf',
            ),
            'folder_id' => $folder->id,
            'visibility' => FileVisibility::Private->value,
        ])->assertRedirect();

        $child = Folder::query()->where('name', 'Nueva privada')->firstOrFail();
        $file = File::query()->where('display_name', 'nuevo.pdf')->firstOrFail();

        $this->assertSame($oldDepartment->id, $child->department_id);
        $this->assertSame($oldDepartment->id, $file->department_id);
        $this->assertStringContainsString(
            "departamentos/{$oldDepartment->id}/",
            $file->path,
        );
        Storage::disk('nube')->assertExists($file->path);
    }

    private function folder(
        User $owner,
        FileVisibility $visibility,
        string $name,
    ): Folder {
        return Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => $name,
            'visibility' => $visibility,
            'collaboration_scope' => $visibility === FileVisibility::Collaborative
                ? CollaborationScope::Department
                : null,
        ]);
    }

    private function file(
        User $owner,
        FileVisibility $visibility,
        string $name,
        ?Folder $folder = null,
    ): File {
        $file = File::factory()->create([
            'folder_id' => $folder?->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => $name,
            'original_name' => $name,
            'visibility' => $visibility,
            'collaboration_scope' => $visibility === FileVisibility::Collaborative
                ? CollaborationScope::Department
                : null,
            'path' => "departamentos/{$owner->department_id}/usuarios/{$owner->id}/{$name}",
        ]);

        Storage::disk('nube')->put($file->path, 'contenido');

        return $file;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions): static
    {
        $this->grant($user, ['nube_inicio_ver', ...$permissions]);

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver', ...$permissions],
            'access.validated_at' => now()->timestamp,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grant(User $user, array $permissions): void
    {
        foreach (array_unique($permissions) as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );

            $user->permissions()->syncWithoutDetaching([
                $permission->id => ['created_at' => now()],
            ]);
        }

        $user->unsetRelation('permissions');
    }

    private function grantRole(User $user, string $roleName): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => $roleName],
        );

        $user->roles()->syncWithoutDetaching([
            $role->id => ['created_at' => now()],
        ]);
        $user->unsetRelation('roles');
    }
}
