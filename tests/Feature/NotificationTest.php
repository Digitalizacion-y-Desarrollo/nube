<?php

namespace Tests\Feature;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_upload_notifies_colleagues_with_department_permission(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['department_id' => $department->id]);
        $noPermissionColleague = User::factory()->create(['department_id' => $department->id]);
        $inactiveColleague = User::factory()->create([
            'department_id' => $department->id,
            'active' => false,
        ]);
        $this->grant($colleague, ['nube_departamento_ver']);
        $this->grant($inactiveColleague, ['nube_departamento_ver']);

        $this->authenticated($uploader, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('reporte.pdf', 100, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Department->value,
            ])
            ->assertRedirect();

        $file = File::query()->firstOrFail();

        $this->assertCount(1, $colleague->fresh()->notifications);
        $notification = $colleague->fresh()->notifications->first();
        $this->assertStringContainsString($file->display_name, $notification->data['message']);
        $this->assertSame(route('folders.department'), $notification->data['url']);
        $this->assertSame($uploader->avatarUrl(), $notification->data['actor_avatar']);
        $this->assertNull($notification->read_at);

        $this->assertCount(0, $noPermissionColleague->fresh()->notifications);
        $this->assertCount(0, $inactiveColleague->fresh()->notifications);
        $this->assertCount(0, $uploader->fresh()->notifications);
    }

    public function test_selected_collaborators_are_notified_when_shared_at_upload(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $collaborator = User::factory()->create(['department_id' => $department->id]);
        $unrelatedColleague = User::factory()->create(['department_id' => $department->id]);

        $this->authenticated($uploader, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$collaborator->id],
                'collaborator_permissions' => [$collaborator->id => ['view', 'download']],
            ])
            ->assertRedirect();

        $file = File::query()->firstOrFail();

        $this->assertCount(1, $collaborator->fresh()->notifications);
        $notification = $collaborator->fresh()->notifications->first();
        $this->assertStringContainsString($file->display_name, $notification->data['message']);
        $this->assertStringContainsString('contigo', $notification->data['message']);

        $this->assertCount(0, $unrelatedColleague->fresh()->notifications);
        $this->assertCount(0, $uploader->fresh()->notifications);
    }

    public function test_public_upload_notifies_users_with_public_permission(): void
    {
        Storage::fake('nube');
        $uploader = User::factory()->create();
        $otherUser = User::factory()->create();
        $noPermissionUser = User::factory()->create();
        $this->grant($otherUser, ['nube_publicos_ver']);

        $this->authenticated($uploader, ['nube_publicos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('circular.pdf', 100, 'application/pdf'),
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect();

        $this->assertCount(1, $otherUser->fresh()->notifications);
        $this->assertCount(0, $noPermissionUser->fresh()->notifications);
        $this->assertCount(0, $uploader->fresh()->notifications);
    }

    public function test_admin_modifying_a_file_notifies_its_owner(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'visibility' => FileVisibility::Private,
            'disk' => 'nube',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');
        $superuser = $this->superuser(withAdministrationPermission: true);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect();

        $this->assertCount(1, $owner->fresh()->notifications);
        $notification = $owner->fresh()->notifications->first();
        $this->assertStringContainsString('superadministrador', $notification->data['message']);
        $this->assertStringContainsString($file->display_name, $notification->data['message']);

        $this->assertCount(0, $superuser->fresh()->notifications);
    }

    public function test_superuser_modifying_their_own_file_does_not_self_notify(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $file = File::factory()->create([
            'owner_id' => $superuser->id,
            'department_id' => $superuser->department_id,
            'visibility' => FileVisibility::Private,
            'disk' => 'nube',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect();

        $this->assertCount(0, $superuser->fresh()->notifications);
    }

    public function test_newly_added_collaborator_is_notified_but_existing_ones_are_not_renotified(): void
    {
        // Un usuario normal solo puede cambiar la clasificación cuando el
        // valor de visibilidad realmente cambia (FilePolicy::changeVisibility),
        // así que reconfigurar colaboradores de un archivo que ya es
        // colaborativo solo es posible por la ruta administrativa, que sí
        // permite Colaborativo -> Colaborativo para reconfigurar el acceso.
        Storage::fake('nube');
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $firstCollaborator = User::factory()->create(['department_id' => $department->id]);
        $secondCollaborator = User::factory()->create(['department_id' => $department->id]);
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
            'disk' => 'nube',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($owner, ['nube.archivos.publicar'])
            ->patch(route('files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$firstCollaborator->id],
                'collaborator_permissions' => [$firstCollaborator->id => ['view']],
            ])
            ->assertRedirect();

        $this->assertCount(1, $firstCollaborator->fresh()->notifications);

        $superuser = $this->superuser(withAdministrationPermission: true);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$firstCollaborator->id, $secondCollaborator->id],
                'collaborator_permissions' => [
                    $firstCollaborator->id => ['view'],
                    $secondCollaborator->id => ['view'],
                ],
            ])
            ->assertRedirect();

        $this->assertCount(1, $firstCollaborator->fresh()->notifications);
        $this->assertCount(1, $secondCollaborator->fresh()->notifications);
    }

    public function test_opening_a_notification_marks_it_read_and_redirects(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['department_id' => $department->id]);
        $this->grant($colleague, ['nube_departamento_ver']);

        $this->authenticated($uploader, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('reporte.pdf', 100, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Department->value,
            ])
            ->assertRedirect();

        $notification = $colleague->fresh()->notifications->first();
        $this->assertNull($notification->read_at);

        $this->authenticated($colleague, ['nube_departamento_ver'])
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('folders.department'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_open_another_users_notification(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['department_id' => $department->id]);
        $stranger = User::factory()->create();
        $this->grant($colleague, ['nube_departamento_ver']);

        $this->authenticated($uploader, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('reporte.pdf', 100, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Department->value,
            ])
            ->assertRedirect();

        $notification = $colleague->fresh()->notifications->first();

        $this->authenticated($stranger)
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_read_all_marks_every_notification_as_read(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['department_id' => $department->id]);
        $this->grant($colleague, ['nube_departamento_ver']);

        foreach (['uno.pdf', 'dos.pdf'] as $name) {
            $this->authenticated($uploader, ['nube_departamento_subir'])
                ->post(route('files.store'), [
                    'file' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
                    'visibility' => FileVisibility::Collaborative->value,
                    'collaboration_scope' => CollaborationScope::Department->value,
                ])
                ->assertRedirect();
        }

        $this->assertSame(2, $colleague->fresh()->unreadNotifications->count());

        $this->authenticated($colleague, ['nube_departamento_ver'])
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $colleague->fresh()->unreadNotifications->count());
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
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

    private function superuser(bool $withAdministrationPermission = false): User
    {
        $user = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['name' => 'superuser'],
            ['display_name' => 'Superusuario'],
        );
        $user->roles()->attach($role, ['created_at' => now()]);

        if ($withAdministrationPermission) {
            $this->grant($user, ['nube_administracion_administrar']);
        }

        $user->unsetRelation('roles');

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions = []): static
    {
        $this->grant($user, ['nube_inicio_ver', ...$permissions]);
        $permissionNames = array_values(array_unique(['nube_inicio_ver', ...$permissions]));

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $permissionNames,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
