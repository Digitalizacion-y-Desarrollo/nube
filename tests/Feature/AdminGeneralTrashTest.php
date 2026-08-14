<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGeneralTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_trash_lists_deleted_items_with_actor_retention_and_storage(): void
    {
        $this->travelTo('2026-08-01 10:00:00');
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $owner = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
        ]);
        $deleter = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Bruno',
            'last_name' => 'López',
            'email' => 'bruno@example.test',
        ]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Informe eliminado.pdf',
            'size_bytes' => 4096,
            'path' => 'departamentos/ruta-que-no-debe-mostrarse.pdf',
        ]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'name' => 'Carpeta eliminada',
        ]);

        $this->actingAs($deleter);
        $file->delete();
        $folder->delete();
        auth()->logout();

        $this->authenticated($superuser)
            ->get(route('admin.trash'))
            ->assertOk()
            ->assertSee('Informe eliminado.pdf')
            ->assertSee('Carpeta eliminada')
            ->assertSee('Ana Pérez')
            ->assertSee('Bruno López')
            ->assertSee('bruno@example.test')
            ->assertSee('01/08/2026 10:00')
            ->assertSee('31/08/2026')
            ->assertSee('4.0 KB')
            ->assertDontSee('departamentos/ruta-que-no-debe-mostrarse.pdf');

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'deleted_by' => $deleter->id,
        ]);
        $this->assertDatabaseHas('folders', [
            'id' => $folder->id,
            'deleted_by' => $deleter->id,
        ]);
    }

    public function test_global_trash_filters_by_person_department_and_date(): void
    {
        $superuser = $this->superuser();
        $technology = Department::factory()->create(['name' => 'Tecnología']);
        $treasury = Department::factory()->create(['name' => 'Tesorería']);
        $anaOwner = User::factory()->create(['department_id' => $technology->id]);
        $brunoOwner = User::factory()->create(['department_id' => $treasury->id]);

        $recent = File::factory()->create([
            'owner_id' => $anaOwner->id,
            'department_id' => $technology->id,
            'display_name' => 'Reciente de Tecnologia.pdf',
        ]);
        $old = File::factory()->create([
            'owner_id' => $brunoOwner->id,
            'department_id' => $treasury->id,
            'display_name' => 'Antiguo de Tesoreria.pdf',
        ]);

        $this->travelTo('2026-08-10 09:00:00');
        $recent->delete();
        $this->travelTo('2026-06-01 09:00:00');
        $old->delete();
        $this->travelBack();

        $this->authenticated($superuser)
            ->get(route('admin.trash', ['department_id' => $technology->id]))
            ->assertOk()
            ->assertSee('Reciente de Tecnologia.pdf')
            ->assertDontSee('Antiguo de Tesoreria.pdf');

        $this->authenticated($superuser)
            ->get(route('admin.trash', ['user_id' => $brunoOwner->id]))
            ->assertOk()
            ->assertSee('Antiguo de Tesoreria.pdf')
            ->assertDontSee('Reciente de Tecnologia.pdf');

        $this->authenticated($superuser)
            ->get(route('admin.trash', ['date_from' => '2026-08-01']))
            ->assertOk()
            ->assertSee('Reciente de Tecnologia.pdf')
            ->assertDontSee('Antiguo de Tesoreria.pdf');

        $this->authenticated($superuser)
            ->get(route('admin.trash', ['q' => 'Antiguo']))
            ->assertOk()
            ->assertSee('Antiguo de Tesoreria.pdf')
            ->assertDontSee('Reciente de Tecnologia.pdf');
    }

    public function test_superuser_without_administration_permission_can_only_consult(): void
    {
        $superuser = $this->superuser();
        $file = File::factory()->create(['display_name' => 'Sólo consulta.pdf']);
        $folder = Folder::factory()->create(['name' => 'Sólo consulta']);
        $file->delete();
        $folder->delete();

        $this->authenticated($superuser)
            ->get(route('admin.trash'))
            ->assertOk()
            ->assertSee('Sólo consulta.pdf');

        $this->authenticated($superuser)
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertForbidden();

        $this->authenticated($superuser)
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Sólo consulta.pdf',
            ])
            ->assertForbidden();

        $this->authenticated($superuser)
            ->post(route('admin.trash.folders.restore', $folder->id))
            ->assertForbidden();

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        $this->assertSoftDeleted('folders', ['id' => $folder->id]);
    }

    public function test_authorized_superuser_restores_a_file_to_its_original_folder_with_audit(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'folder_id' => $folder->id,
            'display_name' => 'Recuperable.pdf',
            'stored_name' => 'recuperable.pdf',
            'disk' => 'nube',
            'path' => "papelera/usuarios/{$owner->id}/recuperable.pdf",
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');
        $this->actingAs($owner);
        $file->delete();
        auth()->logout();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertRedirect();

        $file->refresh();
        $this->assertFalse($file->trashed());
        $this->assertNull($file->deleted_by);
        $this->assertSame($folder->id, $file->folder_id);
        $this->assertSame(
            "departamentos/{$department->id}/usuarios/{$owner->id}/privados/carpetas/{$folder->id}/recuperable.pdf",
            $file->path,
        );
        Storage::disk('nube')->assertExists($file->path);
        Storage::disk('nube')->assertMissing("papelera/usuarios/{$owner->id}/recuperable.pdf");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.trash.file_restored',
            'resource_id' => $file->id,
        ]);
    }

    public function test_file_returns_to_the_root_when_its_original_folder_is_also_deleted(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'folder_id' => $folder->id,
            'stored_name' => 'huerfano.pdf',
            'disk' => 'nube',
            'path' => "papelera/usuarios/{$owner->id}/huerfano.pdf",
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');
        $file->delete();
        $folder->delete();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertRedirect();

        $file->refresh();
        $this->assertFalse($file->trashed());
        $this->assertNull($file->folder_id);
        $this->assertSame(
            "departamentos/{$department->id}/usuarios/{$owner->id}/privados/raiz/huerfano.pdf",
            $file->path,
        );
    }

    public function test_folder_is_restored_to_the_root_when_its_parent_is_unavailable(): void
    {
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $parent = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'name' => 'Superior',
            'path_cache' => '/Superior',
        ]);
        $child = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'parent_id' => $parent->id,
            'name' => 'Dependiente',
            'path_cache' => '/Superior/Dependiente',
        ]);

        $child->delete();
        $parent->delete();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->post(route('admin.trash.folders.restore', $child->id))
            ->assertRedirect();

        $child->refresh();
        $this->assertFalse($child->trashed());
        $this->assertNull($child->parent_id);
        $this->assertNull($child->deleted_by);
        $this->assertSame('/Dependiente', $child->path_cache);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.trash.folder_restored',
            'resource_id' => $child->id,
        ]);
    }

    public function test_permanent_deletion_requires_the_exact_name_and_removes_the_physical_file(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $owner = User::factory()->create();
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => 'Definitivo.pdf',
            'disk' => 'nube',
            'path' => "papelera/usuarios/{$owner->id}/definitivo.pdf",
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');
        $file->delete();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->from(route('admin.trash'))
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Definitivo',
            ])
            ->assertSessionHasErrors('confirmation');

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        Storage::disk('nube')->assertExists($file->path);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Definitivo.pdf',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('nube')->assertMissing("papelera/usuarios/{$owner->id}/definitivo.pdf");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.trash.file_purged',
            'resource_id' => $file->id,
        ]);
    }

    public function test_folder_purge_is_blocked_while_it_still_retains_content(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $owner = User::factory()->create();
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Con contenido',
        ]);
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'folder_id' => $folder->id,
            'display_name' => 'Retenido.pdf',
            'disk' => 'nube',
            'path' => "papelera/usuarios/{$owner->id}/retenido.pdf",
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');
        $file->delete();
        $folder->delete();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->from(route('admin.trash'))
            ->delete(route('admin.trash.folders.purge', $folder->id), [
                'confirmation' => 'Con contenido',
            ])
            ->assertRedirect(route('admin.trash'))
            ->assertSessionHas('admin_trash_error');

        $this->assertSoftDeleted('folders', ['id' => $folder->id]);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Retenido.pdf',
            ])
            ->assertRedirect();

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.trash.folders.purge', $folder->id), [
                'confirmation' => 'Con contenido',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.trash.folder_purged',
            'resource_id' => $folder->id,
        ]);
    }

    private function superuser(bool $withAdministrationPermission = false): User
    {
        $user = User::factory()->create([
            'name' => 'Supervisión',
            'last_name' => 'Central',
            'email' => 'superusuario@example.test',
        ]);
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);
        $user->roles()->attach($role, ['created_at' => now()]);

        if ($withAdministrationPermission) {
            $permission = Permission::factory()->create([
                'name' => 'nube_administracion_administrar',
                'display_name' => 'Administrar nube',
            ]);
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions = []): static
    {
        $permissionNames = array_values(array_unique([
            'nube_inicio_ver',
            ...$permissions,
        ]));

        foreach ($permissionNames as $permissionName) {
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
            'access.permissions' => $permissionNames,
            'access.roles' => ['superuser'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
