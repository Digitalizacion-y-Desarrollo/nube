<?php

namespace Tests\Feature;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFileExplorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_explorer_filters_the_global_inventory_and_preserves_safe_metadata(): void
    {
        $technology = Department::factory()->create(['name' => 'Tecnología']);
        $finance = Department::factory()->create(['name' => 'Finanzas']);
        $superuser = $this->superuser();
        $owner = User::factory()->create([
            'department_id' => $technology->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
        ]);
        $otherOwner = User::factory()->create(['department_id' => $finance->id]);

        File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $technology->id,
            'display_name' => 'Informe estratégico.pdf',
            'original_name' => 'Informe estratégico.pdf',
            'extension' => 'pdf',
            'visibility' => FileVisibility::Collaborative,
            'uploaded_at' => '2026-08-05 10:00:00',
        ]);
        File::factory()->create([
            'owner_id' => $otherOwner->id,
            'department_id' => $finance->id,
            'display_name' => 'Informe financiero.pdf',
            'original_name' => 'Informe financiero.pdf',
            'extension' => 'pdf',
            'visibility' => FileVisibility::Collaborative,
            'uploaded_at' => '2026-08-05 10:00:00',
        ]);
        File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $technology->id,
            'display_name' => 'Informe antiguo.xlsx',
            'original_name' => 'Informe antiguo.xlsx',
            'extension' => 'xlsx',
            'visibility' => FileVisibility::Private,
            'uploaded_at' => '2026-07-01 10:00:00',
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.files', [
                'q' => 'estratégico',
                'department_id' => $technology->id,
                'user_id' => $owner->id,
                'visibility' => 'collaborative',
                'type' => 'PDF',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-10',
                'status' => 'active',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Informe estratégico.pdf')
            ->assertDontSee('Informe financiero.pdf')
            ->assertDontSee('Informe antiguo.xlsx')
            ->assertSee('1 resultado');
    }

    public function test_superuser_can_view_metadata_but_sensitive_storage_fields_remain_hidden(): void
    {
        $superuser = $this->superuser();
        $file = File::factory()->create([
            'display_name' => 'Convenio público.pdf',
            'original_name' => 'Convenio original.pdf',
            'stored_name' => 'secreto-fisico.pdf',
            'path' => 'departamentos/1/usuarios/2/privados/secreto-fisico.pdf',
            'checksum' => 'checksum-que-no-debe-mostrarse',
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.files.show', $file->id))
            ->assertOk()
            ->assertSee('Convenio público.pdf')
            ->assertSee('Convenio original.pdf')
            ->assertSee($file->id)
            ->assertDontSee('secreto-fisico.pdf')
            ->assertDontSee('departamentos/1/usuarios/2/privados')
            ->assertDontSee('checksum-que-no-debe-mostrarse');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.file.metadata_viewed',
            'resource_id' => $file->id,
        ]);
    }

    public function test_superuser_without_functional_permission_cannot_operate_files(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser();
        $file = File::factory()->create([
            'disk' => 'nube',
            'path' => 'temporales/restringido.pdf',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($superuser)
            ->get(route('admin.files.download', $file))
            ->assertForbidden();

        $this->authenticated($superuser)
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertForbidden();

        $this->authenticated($superuser)
            ->delete(route('admin.files.destroy', $file))
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $superuser->id,
            'resource_id' => $file->id,
        ]);
    }

    public function test_authorized_superuser_downloads_reclassifies_and_trashes_with_audit(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $owner = User::factory()->create();

        $downloadable = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => 'Descarga segura.pdf',
            'stored_name' => 'descarga.pdf',
            'disk' => 'nube',
            'path' => 'temporales/descarga.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($downloadable->path, 'contenido seguro');

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->get(route('admin.files.download', $downloadable))
            ->assertOk()
            ->assertDownload('Descarga segura.pdf');

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->patch(route('admin.files.visibility', $downloadable), [
                'file_context' => $downloadable->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect();

        $downloadable->refresh();
        $this->assertSame(FileVisibility::Public, $downloadable->visibility);
        Storage::disk('nube')->assertExists($downloadable->path);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->delete(route('admin.files.destroy', $downloadable))
            ->assertRedirect();

        $this->assertSoftDeleted('files', ['id' => $downloadable->id]);

        foreach ([
            'admin.file.downloaded',
            'admin.file.visibility_changed',
            'admin.file.trashed',
        ] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $superuser->id,
                'action' => $action,
                'resource_id' => $downloadable->id,
            ]);
        }

        $this->assertFalse(
            AuditLog::query()
                ->where('user_id', $superuser->id)
                ->get()
                ->contains(fn (AuditLog $log): bool => str_contains(
                    json_encode($log->details, JSON_THROW_ON_ERROR),
                    'temporales/descarga.pdf',
                )),
        );
    }

    public function test_authorized_superuser_selects_the_department_or_specific_people_when_collaborating(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create(['name' => 'Tecnología']);
        $otherDepartment = Department::factory()->create(['name' => 'Finanzas']);
        $superuser = $this->superuser(withAdministrationPermission: true);
        $owner = User::factory()->create(['department_id' => $department->id]);
        $collaborator = User::factory()->create([
            'department_id' => $department->id,
            'active' => true,
        ]);
        $outsider = User::factory()->create([
            'department_id' => $otherDepartment->id,
            'active' => true,
        ]);
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'disk' => 'nube',
            'path' => 'temporales/colaboracion.pdf',
            'stored_name' => 'colaboracion.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->get(route('admin.files'))
            ->assertOk()
            ->assertSee('Todo el departamento Tecnología')
            ->assertSee($collaborator->email);

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Department->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $file->refresh();
        $this->assertSame(FileVisibility::Collaborative, $file->visibility);
        $this->assertSame(CollaborationScope::Department, $file->collaboration_scope);
        $this->assertCount(0, $file->collaborators);
        $collaborativePath = $file->path;

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$collaborator->id],
                'collaborator_permissions' => [
                    $collaborator->id => ['view', 'download', 'rename'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $file->refresh();
        $this->assertSame(CollaborationScope::Selected, $file->collaboration_scope);
        $this->assertSame($collaborativePath, $file->path);
        $this->assertDatabaseHas('file_collaborators', [
            'file_id' => $file->id,
            'user_id' => $collaborator->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => true,
            'can_move' => false,
            'can_delete' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superuser->id,
            'action' => 'admin.file.sharing_configured',
            'resource_id' => $file->id,
        ]);

        $reopenedForm = $this->authenticated(
            $superuser,
            ['nube_administracion_administrar'],
        )->get(route('admin.files'));
        $reopenedForm->assertOk();
        $renderedHtml = $reopenedForm->getContent();
        $this->assertMatchesRegularExpression(
            '/name="collaborators\[\]"\s+value="'.$collaborator->id.'"[^>]*checked/s',
            $renderedHtml,
        );
        $this->assertMatchesRegularExpression(
            '/name="collaborator_permissions\['.$collaborator->id.'\]\[\]"\s+value="download"[^>]*checked/s',
            $renderedHtml,
        );
        $this->assertMatchesRegularExpression(
            '/name="collaborator_permissions\['.$collaborator->id.'\]\[\]"\s+value="rename"[^>]*checked/s',
            $renderedHtml,
        );

        $this->authenticated($superuser, ['nube_administracion_administrar'])
            ->from(route('admin.files'))
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$outsider->id],
                'collaborator_permissions' => [
                    $outsider->id => ['view'],
                ],
            ])
            ->assertRedirect(route('admin.files'))
            ->assertSessionHasErrors('collaborators.0', null, 'adminFileVisibility');

        $this->assertDatabaseMissing('file_collaborators', [
            'file_id' => $file->id,
            'user_id' => $outsider->id,
        ]);
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
