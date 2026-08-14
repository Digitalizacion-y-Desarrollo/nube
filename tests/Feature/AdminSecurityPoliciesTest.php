<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Http\Middleware\EnsureAdministrativePermission;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSecurityPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_write_routes_require_both_the_role_and_the_permission(): void
    {
        Storage::fake('nube');
        [$file, $trashedFile, $trashedFolder] = $this->resources();

        $routes = [
            ['get', route('admin.files.download', $file)],
            ['patch', route('admin.files.visibility', $file)],
            ['delete', route('admin.files.destroy', $file)],
            ['post', route('admin.trash.files.restore', $trashedFile->id)],
            ['delete', route('admin.trash.files.purge', $trashedFile->id)],
            ['post', route('admin.trash.folders.restore', $trashedFolder->id)],
            ['delete', route('admin.trash.folders.purge', $trashedFolder->id)],
        ];

        $superuserWithoutPermission = $this->superuser();
        $regularUser = User::factory()->create();

        // Las comprobaciones se agrupan por actor: `actingAs` persiste entre
        // peticiones, así que mezclarlas dejaría de probar al invitado real.
        foreach ($routes as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }

        foreach ($routes as [$method, $url]) {
            $this->authenticated($regularUser, superuser: false)
                ->{$method}($url)
                ->assertForbidden();
        }

        foreach ($routes as [$method, $url]) {
            $this->authenticated($superuserWithoutPermission)
                ->{$method}($url)
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $superuserWithoutPermission->id,
        ]);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('files', ['id' => $trashedFile->id]);
        $this->assertSoftDeleted('folders', ['id' => $trashedFolder->id]);
    }

    public function test_losing_the_permission_in_the_session_closes_administrative_writes(): void
    {
        Storage::fake('nube');
        [$file] = $this->resources();

        // La copia local conserva el permiso, pero Accesos dejó de devolverlo en
        // la última revalidación: el middleware debe cerrar la escritura igual.
        $superuser = $this->superuser(withAdministrationPermission: true);

        $this->actingAs($superuser)
            ->withSession([
                'access.token' => 'test-token',
                'access.permissions' => ['nube_inicio_ver'],
                'access.roles' => ['superuser'],
                'access.validated_at' => now()->timestamp,
            ])
            ->delete(route('admin.files.destroy', $file))
            ->assertForbidden();

        $this->assertTrue(
            $superuser->hasPermission(EnsureAdministrativePermission::PERMISSION),
        );
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    public function test_every_administrative_mutation_leaves_an_audit_trail(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Auditable.pdf',
            'stored_name' => 'auditable.pdf',
            'disk' => 'nube',
            'path' => 'temporales/auditable.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $session = ['nube_administracion_administrar'];

        $this->authenticated($superuser, $session)
            ->get(route('admin.files.show', $file))
            ->assertOk();

        $this->authenticated($superuser, $session)
            ->get(route('admin.files.download', $file))
            ->assertOk();

        $this->authenticated($superuser, $session)
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect();

        $this->authenticated($superuser, $session)
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect();

        $this->authenticated($superuser, $session)
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertRedirect();

        $this->authenticated($superuser, $session)
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect();

        $this->authenticated($superuser, $session)
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Auditable.pdf',
            ])
            ->assertRedirect();

        $expected = [
            'admin.file.metadata_viewed',
            'admin.file.downloaded',
            'admin.file.visibility_changed',
            'admin.file.trashed',
            'admin.trash.file_restored',
            'admin.trash.file_purged',
        ];

        foreach ($expected as $action) {
            $log = AuditLog::query()
                ->where('action', $action)
                ->where('user_id', $superuser->id)
                ->first();

            $this->assertNotNull($log, "Falta el evento de auditoría {$action}.");
            $this->assertSame($file->id, $log->resource_id);
            $this->assertNotNull($log->ip_address, "El evento {$action} no registró la IP.");
        }
    }

    public function test_administrative_routes_reject_traversal_and_unknown_identifiers(): void
    {
        $superuser = $this->superuser(withAdministrationPermission: true);
        $session = ['nube_administracion_administrar'];

        $targets = [
            ['get', '/admin/archivos/no-es-un-uuid'],
            ['get', '/admin/archivos/no-es-un-uuid/descargar'],
            ['get', '/admin/archivos/%2E%2E%2F%2E%2E%2F.env/descargar'],
            ['delete', '/admin/archivos/%2E%2E%2F%2E%2E%2F.env'],
            ['post', '/admin/papelera/archivos/%2E%2E%2F%2E%2E%2F.env/restaurar'],
            ['delete', '/admin/papelera/carpetas/no-es-un-uuid'],
        ];

        foreach ($targets as [$method, $url]) {
            $this->authenticated($superuser, $session)
                ->{$method}($url)
                ->assertNotFound();
        }

        $this->get('/storage/app/nube/departamentos/archivo.pdf')->assertNotFound();
    }

    public function test_active_files_cannot_be_purged_and_trashed_files_cannot_be_downloaded(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $session = ['nube_administracion_administrar'];

        $active = File::factory()->create([
            'display_name' => 'Activo.pdf',
            'disk' => 'nube',
            'path' => 'temporales/activo.pdf',
        ]);
        Storage::disk('nube')->put($active->path, 'contenido');

        $trashed = File::factory()->create([
            'display_name' => 'Eliminado.pdf',
            'disk' => 'nube',
            'path' => 'papelera/eliminado.pdf',
        ]);
        Storage::disk('nube')->put($trashed->path, 'contenido');
        $trashed->delete();

        // Un archivo activo no está en la papelera: la ruta de purga no lo alcanza.
        $this->authenticated($superuser, $session)
            ->delete(route('admin.trash.files.purge', $active->id), [
                'confirmation' => 'Activo.pdf',
            ])
            ->assertNotFound();

        // Un archivo en papelera no puede descargarse ni reclasificarse: la
        // resolución del modelo excluye los eliminados, así que la ruta ni
        // siquiera llega a la Policy y responde 404.
        $this->authenticated($superuser, $session)
            ->get(route('admin.files.download', $trashed->id))
            ->assertNotFound();

        $this->authenticated($superuser, $session)
            ->patch(route('admin.files.visibility', $trashed->id), [
                'file_context' => $trashed->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertNotFound();

        $trashed->refresh();
        $this->assertTrue($trashed->trashed());
        Storage::disk('nube')->assertExists('papelera/eliminado.pdf');

        $this->assertDatabaseHas('files', ['id' => $active->id, 'deleted_at' => null]);
        Storage::disk('nube')->assertExists('temporales/activo.pdf');
    }

    public function test_failed_administrative_operations_do_not_expose_physical_paths(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $owner = User::factory()->create();

        // El registro apunta a un archivo físico inexistente.
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => 'Sin copia fisica.pdf',
            'disk' => 'nube',
            'path' => 'papelera/ruta-que-no-debe-mostrarse.pdf',
        ]);
        $file->delete();

        $response = $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->from(route('admin.trash'))
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertRedirect(route('admin.trash'))
            ->assertSessionHas('admin_trash_error');

        $message = (string) $response->getSession()->get('admin_trash_error');

        $this->assertStringNotContainsString('papelera/ruta-que-no-debe-mostrarse.pdf', $message);
        $this->assertStringNotContainsString('storage', $message);
        $this->assertStringNotContainsString(base_path(), $message);
        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    /**
     * @return array{0: File, 1: File, 2: Folder}
     */
    private function resources(): array
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Protegido.pdf',
            'disk' => 'nube',
            'path' => 'temporales/protegido.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $trashedFile = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'En papelera.pdf',
            'disk' => 'nube',
            'path' => 'papelera/en-papelera.pdf',
        ]);
        Storage::disk('nube')->put($trashedFile->path, 'contenido');
        $trashedFile->delete();

        $trashedFolder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'name' => 'Carpeta en papelera',
        ]);
        $trashedFolder->delete();

        return [$file, $trashedFile, $trashedFolder];
    }

    private function superuser(bool $withAdministrationPermission = false): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'name' => 'superuser',
            'display_name' => 'Superusuario',
        ]);
        $user->roles()->attach($role, ['created_at' => now()]);

        if ($withAdministrationPermission) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => EnsureAdministrativePermission::PERMISSION],
                ['display_name' => 'Administrar nube'],
            );
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(
        User $user,
        array $permissions = [],
        bool $superuser = true,
    ): static {
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
            'access.roles' => $superuser ? ['superuser'] : [],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
