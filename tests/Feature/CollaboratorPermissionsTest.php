<?php

namespace Tests\Feature;

use App\Enums\CollaborationScope;
use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaboratorPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_collaboration_form_renders_permissions_for_each_department_user(): void
    {
        $department = Department::factory()->create(['name' => 'Tecnología']);
        $juan = User::factory()->create(['department_id' => $department->id]);
        $tamara = User::factory()->create(['department_id' => $department->id]);

        Http::fake([
            '*/api/integrations/users*' => Http::response([
                'data' => [[
                    'id' => $tamara->external_id,
                    'name' => $tamara->name,
                    'apellido_paterno' => $tamara->last_name,
                    'email' => $tamara->email,
                    'departamento' => $department->name,
                    'activo' => true,
                ]],
            ]),
        ]);

        $this->authenticated($juan, [
            'nube_departamento_ver',
            'nube_departamento_crear_carpeta',
            'nube_departamento_subir',
        ])->get(route('folders.department'))
            ->assertOk()
            ->assertSee('configura sus permisos internos')
            ->assertSee(
                'name="collaborator_permissions['.$tamara->id.'][]"',
                false,
            )
            ->assertSee('value="view"', false)
            ->assertSee('value="download"', false)
            ->assertSee('value="rename"', false)
            ->assertSee('value="move"', false)
            ->assertSee('value="delete"', false);
    }

    public function test_owner_assigns_internal_permissions_to_each_folder_collaborator(): void
    {
        $department = Department::factory()->create();
        $juan = User::factory()->create(['department_id' => $department->id]);
        $tamara = User::factory()->create(['department_id' => $department->id]);
        $francisco = User::factory()->create(['department_id' => $department->id]);

        $this->authenticated($juan, ['nube_departamento_crear_carpeta'])
            ->post(route('folders.store'), [
                'name' => 'Proyecto compartido',
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$tamara->id, $francisco->id],
                'collaborator_permissions' => [
                    $tamara->id => ['view', 'download', 'rename'],
                    $francisco->id => ['view', 'download'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $folder = Folder::query()
            ->where('name', 'Proyecto compartido')
            ->firstOrFail();

        $this->assertDatabaseHas('folder_collaborators', [
            'folder_id' => $folder->id,
            'user_id' => $tamara->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => true,
            'can_move' => false,
            'can_delete' => false,
        ]);
        $this->assertDatabaseHas('folder_collaborators', [
            'folder_id' => $folder->id,
            'user_id' => $francisco->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => false,
            'can_move' => false,
            'can_delete' => false,
        ]);
    }

    public function test_owner_assigns_internal_permissions_when_uploading_a_file(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $juan = User::factory()->create(['department_id' => $department->id]);
        $tamara = User::factory()->create(['department_id' => $department->id]);
        $francisco = User::factory()->create(['department_id' => $department->id]);

        $this->authenticated($juan, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'acuerdos.pdf',
                    10,
                    'application/pdf',
                ),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$tamara->id, $francisco->id],
                'collaborator_permissions' => [
                    $tamara->id => ['view', 'download', 'rename'],
                    $francisco->id => ['view', 'download'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file = File::query()->where('display_name', 'acuerdos.pdf')->firstOrFail();
        $this->assertDatabaseHas('file_collaborators', [
            'file_id' => $file->id,
            'user_id' => $tamara->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => true,
            'can_move' => false,
            'can_delete' => false,
        ]);
        $this->assertDatabaseHas('file_collaborators', [
            'file_id' => $file->id,
            'user_id' => $francisco->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => false,
            'can_move' => false,
            'can_delete' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $juan->id,
            'action' => 'file.sharing_configured',
            'resource_id' => $file->id,
        ]);
    }

    public function test_file_policy_combines_api_permissions_with_internal_grants(): void
    {
        $department = Department::factory()->create();
        $juan = User::factory()->create(['department_id' => $department->id]);
        $tamara = User::factory()->create(['department_id' => $department->id]);
        $francisco = User::factory()->create(['department_id' => $department->id]);
        $operador = User::factory()->create(['department_id' => $department->id]);
        $sinPermisoApi = User::factory()->create(['department_id' => $department->id]);

        $destination = Folder::factory()->create([
            'owner_id' => $juan->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
        ]);
        $file = File::factory()->create([
            'owner_id' => $juan->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
        ]);

        $file->collaborators()->sync([
            $tamara->id => $this->pivot(['view', 'download', 'rename']),
            $francisco->id => $this->pivot(['view', 'download']),
            $operador->id => $this->pivot(['view', 'download', 'rename', 'move', 'delete']),
            $sinPermisoApi->id => $this->pivot(['view', 'rename']),
        ]);
        $destination->collaborators()->sync([
            $operador->id => $this->pivot(['view']),
        ]);

        $functionalPermissions = [
            'nube_departamento_ver',
            'nube_departamento_descargar',
            'nube_departamento_renombrar',
            'nube_departamento_mover',
            'nube_departamento_eliminar',
        ];
        $this->grant($tamara, $functionalPermissions);
        $this->grant($francisco, $functionalPermissions);
        $this->grant($operador, $functionalPermissions);
        $this->grant($sinPermisoApi, ['nube_departamento_ver']);

        $this->assertTrue(Gate::forUser($tamara)->allows('view', $file));
        $this->assertTrue(Gate::forUser($tamara)->allows('download', $file));
        $this->assertTrue(Gate::forUser($tamara)->allows('update', $file));
        $this->assertFalse(Gate::forUser($tamara)->allows('move', [$file, $destination]));
        $this->assertFalse(Gate::forUser($tamara)->allows('delete', $file));

        $this->assertTrue(Gate::forUser($francisco)->allows('view', $file));
        $this->assertTrue(Gate::forUser($francisco)->allows('download', $file));
        $this->assertFalse(Gate::forUser($francisco)->allows('update', $file));

        $this->assertTrue(Gate::forUser($operador)->allows('update', $file));
        $this->assertTrue(Gate::forUser($operador)->allows('move', [$file, $destination]));
        $this->assertTrue(Gate::forUser($operador)->allows('delete', $file));

        $this->assertFalse(Gate::forUser($sinPermisoApi)->allows('update', $file));
    }

    public function test_collaborator_with_move_permission_can_move_a_shared_folder(): void
    {
        $department = Department::factory()->create();
        $juan = User::factory()->create(['department_id' => $department->id]);
        $tamara = User::factory()->create(['department_id' => $department->id]);
        $source = Folder::factory()->create([
            'owner_id' => $juan->id,
            'department_id' => $department->id,
            'name' => 'Origen',
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
            'path_cache' => '/Origen',
        ]);
        $destination = Folder::factory()->create([
            'owner_id' => $juan->id,
            'department_id' => $department->id,
            'name' => 'Destino',
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
            'path_cache' => '/Destino',
        ]);
        $source->collaborators()->sync([
            $tamara->id => $this->pivot(['view', 'move']),
        ]);
        $destination->collaborators()->sync([
            $tamara->id => $this->pivot(['view']),
        ]);

        $this->authenticated($tamara, [
            'nube_departamento_ver',
            'nube_departamento_mover',
        ])->patch(route('folders.move', $source), [
            'folder_context' => $source->id,
            'destination_folder_id' => $destination->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $source->refresh();
        $this->assertSame($destination->id, $source->parent_id);
        $this->assertSame('/Destino/Origen', $source->path_cache);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $tamara->id,
            'action' => 'folder.moved',
            'resource_id' => $source->id,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, bool|Carbon>
     */
    private function pivot(array $permissions): array
    {
        return [
            'can_view' => in_array('view', $permissions, true),
            'can_download' => in_array('download', $permissions, true),
            'can_rename' => in_array('rename', $permissions, true),
            'can_move' => in_array('move', $permissions, true),
            'can_delete' => in_array('delete', $permissions, true),
            'created_at' => now(),
        ];
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

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions): static
    {
        $allPermissions = array_values(array_unique([
            'nube_inicio_ver',
            ...$permissions,
        ]));
        $this->grant($user, $allPermissions);

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $allPermissions,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
