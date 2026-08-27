<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DragAndDropUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dropped_folder_preserves_its_structure_and_uploads_its_file(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $response = $this->authenticated($user, [
            'nube_mis_archivos_subir',
            'nube_mis_archivos_crear_carpeta',
        ])->withHeader('Accept', 'application/json')->post(route('files.drop-store'), [
            'kind' => 'file',
            'relative_path' => 'Expedientes/2026/contrato.pdf',
            'file' => UploadedFile::fake()->create('contrato.pdf', 32, 'application/pdf'),
            'visibility' => 'private',
        ]);

        $response->assertCreated()->assertJson(['kind' => 'file']);

        $parent = Folder::query()->where('name', 'Expedientes')->firstOrFail();
        $child = Folder::query()->where('name', '2026')->firstOrFail();
        $file = File::query()->firstOrFail();

        $this->assertNull($parent->parent_id);
        $this->assertSame('/Expedientes', $parent->path_cache);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('/Expedientes/2026', $child->path_cache);
        $this->assertSame($child->id, $file->folder_id);
        $this->assertSame('contrato.pdf', $file->display_name);
        Storage::disk('nube')->assertExists($file->path);
        $this->assertDatabaseCount('folders', 2);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.created',
            'resource_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.uploaded',
            'resource_id' => $file->id,
        ]);
    }

    public function test_an_empty_dropped_folder_is_created_and_existing_paths_are_reused(): void
    {
        $user = User::factory()->create();
        $permissions = ['nube_mis_archivos_crear_carpeta'];

        foreach (['Archivo histórico', 'Archivo histórico/Vacía'] as $path) {
            $this->authenticated($user, $permissions)
                ->withHeader('Accept', 'application/json')
                ->post(route('files.drop-store'), [
                    'kind' => 'directory',
                    'relative_path' => $path,
                    'visibility' => 'private',
                ])
                ->assertCreated()
                ->assertJson(['kind' => 'directory']);
        }

        $this->assertDatabaseCount('folders', 2);
        $this->assertDatabaseHas('folders', [
            'name' => 'Vacía',
            'path_cache' => '/Archivo histórico/Vacía',
        ]);
    }

    public function test_a_dropped_path_cannot_traverse_or_spoof_its_file_name(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $permissions = [
            'nube_mis_archivos_subir',
            'nube_mis_archivos_crear_carpeta',
        ];

        $this->authenticated($user, $permissions)
            ->withHeader('Accept', 'application/json')
            ->post(route('files.drop-store'), [
                'kind' => 'file',
                'relative_path' => '../secreto.pdf',
                'file' => UploadedFile::fake()->create('secreto.pdf', 10, 'application/pdf'),
                'visibility' => 'private',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('relative_path');

        $this->authenticated($user, $permissions)
            ->withHeader('Accept', 'application/json')
            ->post(route('files.drop-store'), [
                'kind' => 'file',
                'relative_path' => 'C:\\usuarios\\secreto.pdf',
                'file' => UploadedFile::fake()->create('secreto.pdf', 10, 'application/pdf'),
                'visibility' => 'private',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('relative_path');

        $this->authenticated($user, $permissions)
            ->withHeader('Accept', 'application/json')
            ->post(route('files.drop-store'), [
                'kind' => 'file',
                'relative_path' => 'aparente.pdf',
                'file' => UploadedFile::fake()->create('real.pdf', 10, 'application/pdf'),
                'visibility' => 'private',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('relative_path');

        $this->assertDatabaseCount('folders', 0);
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::disk('nube')->allFiles());
    }

    public function test_uploading_a_nested_file_requires_permission_to_create_folders(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->withHeader('Accept', 'application/json')
            ->post(route('files.drop-store'), [
                'kind' => 'file',
                'relative_path' => 'Sin permiso/documento.pdf',
                'file' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
                'visibility' => 'private',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('folders', 0);
        $this->assertDatabaseCount('files', 0);
    }

    public function test_explorer_exposes_the_drop_zone_only_to_users_who_can_upload(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_mis_archivos_ver', 'nube_mis_archivos_subir'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('data-drop-upload', false)
            ->assertSee('data-drop-upload-form-target', false)
            ->assertSee('data-upload-lock', false)
            ->assertSee('Arrastra aquí archivos o carpetas')
            ->assertSee('Sólo quedará seleccionado')
            ->assertSee('No cierres, recargues ni cambies de página')
            ->assertSee(route('files.drop-store'));

        $other = User::factory()->create();

        $this->authenticated($other, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertDontSee('data-drop-upload', false);
    }

    public function test_edit_modal_explains_that_it_renames_without_replacing_content(): void
    {
        $user = User::factory()->create();
        File::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'visibility' => 'private',
            'display_name' => 'Contrato.pdf',
        ]);

        $this->authenticated($user, ['nube_mis_archivos_ver', 'nube_mis_archivos_renombrar'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('Esta edición sólo cambia el nombre visible')
            ->assertSee('usa el modal de subida y arrastra el contenido allí');
    }

    /** @param list<string> $permissions */
    private function authenticated(User $user, array $permissions): static
    {
        $allPermissions = array_values(array_unique(['nube_inicio_ver', ...$permissions]));

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
