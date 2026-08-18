<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Antes de este cambio, el detalle de un archivo (admin.files.show) era de
 * solo lectura: para descargarlo, reclasificarlo o enviarlo a papelera había
 * que volver al listado y encontrarlo de nuevo. Estas pruebas confirman que
 * las mismas acciones ya disponibles en el listado ahora también funcionan
 * directamente desde el detalle.
 */
class AdminFileDetailActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_detail_page_offers_no_actions_without_the_administration_permission(): void
    {
        $superuser = $this->superuser();
        $file = File::factory()->create();

        $this->authenticated($superuser)
            ->get(route('admin.files.show', $file))
            ->assertOk()
            ->assertDontSee(route('admin.files.download', $file), false)
            ->assertDontSee('data-modal-open="admin-visibility-', false)
            ->assertDontSee('data-modal-open="admin-delete-', false)
            ->assertSee('nube_administracion_administrar');
    }

    public function test_an_authorized_superuser_can_download_reclassify_and_trash_from_the_detail_page(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Detalle.pdf',
            'stored_name' => 'detalle.pdf',
            'disk' => 'nube',
            'path' => 'temporales/detalle.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($superuser)
            ->get(route('admin.files.show', $file))
            ->assertOk()
            ->assertSee('Descargar')
            ->assertSee('Reclasificar')
            ->assertSee('Enviar a papelera')
            ->assertSee(route('admin.files.visibility', $file), false)
            ->assertSee(route('admin.files.destroy', $file), false);

        $this->authenticated($superuser)
            ->get(route('admin.files.download', $file))
            ->assertOk()
            ->assertDownload('Detalle.pdf');

        $this->authenticated($superuser)
            ->from(route('admin.files.show', $file))
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect(route('admin.files.show', $file))
            ->assertSessionHas('status');

        $file->refresh();
        $this->assertSame(FileVisibility::Public, $file->visibility);

        $this->authenticated($superuser)
            ->from(route('admin.files.show', $file))
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect(route('admin.files.show', $file))
            ->assertSessionHas('status');

        $this->assertSoftDeleted('files', ['id' => $file->id]);

        // Una vez en papelera, la página de detalle deja de ofrecer acciones.
        $this->authenticated($superuser)
            ->get(route('admin.files.show', $file))
            ->assertOk()
            ->assertDontSee('data-modal-open="admin-delete-', false)
            ->assertSee('En papelera');
    }

    private function superuser(bool $withAdministrationPermission = false): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superuser', 'display_name' => 'Superusuario']);
        $user->roles()->attach($role, ['created_at' => now()]);

        if ($withAdministrationPermission) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => 'nube_administracion_administrar'],
                ['display_name' => 'Administrar nube'],
            );
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user;
    }

    private function authenticated(User $user): static
    {
        $permissionNames = ['nube_inicio_ver'];

        if ($user->hasPermission('nube_administracion_administrar')) {
            $permissionNames[] = 'nube_administracion_administrar';
        }

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['display_name' => $permissionName],
            );
            $user->permissions()->syncWithoutDetaching([$permission->id => ['created_at' => now()]]);
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
