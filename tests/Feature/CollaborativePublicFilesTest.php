<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaborativePublicFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_can_be_uploaded_directly_as_collaborative_or_public(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('equipo.pdf', 10, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->authenticated($user, ['nube_publicos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('general.pdf', 10, 'application/pdf'),
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $collaborative = File::query()->where('display_name', 'equipo.pdf')->firstOrFail();
        $public = File::query()->where('display_name', 'general.pdf')->firstOrFail();

        $this->assertSame(FileVisibility::Collaborative, $collaborative->visibility);
        $this->assertStringContainsString('/colaborativos/raiz/', $collaborative->path);
        $this->assertSame(FileVisibility::Public, $public->visibility);
        $this->assertStringContainsString('/publicos/raiz/', $public->path);
        Storage::disk('nube')->assertExists($collaborative->path);
        Storage::disk('nube')->assertExists($public->path);
    }

    public function test_collaborative_download_is_limited_to_the_same_department(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $coworker = User::factory()->create(['department_id' => $department->id]);
        $outsider = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Collaborative, 'interno.pdf');

        $this->authenticated($coworker, ['nube_departamento_descargar'])
            ->get(route('files.download', $file))
            ->assertOk()
            ->assertDownload('interno.pdf');

        $this->authenticated($outsider, ['nube_departamento_descargar'])
            ->get(route('files.download', $file))
            ->assertForbidden();
    }

    public function test_public_file_can_be_downloaded_from_another_department(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Public, 'publico.pdf');

        $this->authenticated($reader, ['nube_publicos_descargar'])
            ->get(route('files.download', $file))
            ->assertOk()
            ->assertDownload('publico.pdf');
    }

    public function test_only_collaborative_owner_can_rename_or_delete_the_file(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $coworker = User::factory()->create(['department_id' => $department->id]);
        $file = $this->storedFile($owner, FileVisibility::Collaborative, 'equipo.pdf');

        $this->authenticated($coworker, [
            'nube_departamento_renombrar',
            'nube_departamento_eliminar',
        ])->patch(route('files.update', $file), [
            'display_name' => 'ajeno.pdf',
            'file_context' => $file->id,
        ])->assertForbidden();

        $this->authenticated($owner, ['nube_departamento_renombrar'])
            ->patch(route('files.update', $file), [
                'display_name' => 'propio.pdf',
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('propio.pdf', $file->fresh()->display_name);
    }

    public function test_public_file_can_be_modified_by_its_owner_or_an_administrator(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $administrator = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Public, 'manual.pdf');

        $this->authenticated($administrator, ['nube_administracion_administrar'])
            ->patch(route('files.update', $file), [
                'display_name' => 'manual institucional.pdf',
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('manual institucional.pdf', $file->fresh()->display_name);
    }

    public function test_owner_can_change_visibility_and_physical_location_with_audit(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'visibility' => FileVisibility::Private,
        ]);
        $file = $this->storedFile($owner, FileVisibility::Private, 'clasificar.pdf', $folder);
        $oldPath = $file->path;

        $this->authenticated($owner, ['nube_mis_archivos_publicar'])
            ->patch(route('files.visibility', $file), [
                'visibility' => FileVisibility::Collaborative->value,
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file->refresh();
        $this->assertSame(FileVisibility::Collaborative, $file->visibility);
        $this->assertNull($file->folder_id);
        $this->assertStringContainsString('/colaborativos/raiz/', $file->path);
        Storage::disk('nube')->assertMissing($oldPath);
        Storage::disk('nube')->assertExists($file->path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'file.visibility_changed',
            'resource_id' => $file->id,
        ]);
    }

    public function test_visibility_cannot_be_changed_without_the_source_publish_permission(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Private, 'privado.pdf');

        $this->authenticated($owner, ['nube_publicos_publicar'])
            ->patch(route('files.visibility', $file), [
                'visibility' => FileVisibility::Public->value,
                'file_context' => $file->id,
            ])
            ->assertForbidden();

        $this->assertSame(FileVisibility::Private, $file->fresh()->visibility);
    }

    public function test_collaborative_file_can_be_deleted_and_restored_to_its_section(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Collaborative, 'recuperable.pdf');

        $this->authenticated($owner, ['nube_departamento_eliminar'])
            ->delete(route('files.destroy', $file))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->authenticated($owner, ['nube_papelera_restaurar'])
            ->post(route('files.restore', $file->id), [
                'destination_folder_id' => null,
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $restored = File::query()->findOrFail($file->id);
        $this->assertSame(FileVisibility::Collaborative, $restored->visibility);
        $this->assertStringContainsString('/colaborativos/raiz/', $restored->path);
        Storage::disk('nube')->assertExists($restored->path);
    }

    public function test_collaborative_section_renders_upload_download_and_visibility_actions(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $file = $this->storedFile($owner, FileVisibility::Collaborative, 'acciones.pdf');

        $this->authenticated($owner, [
            'nube_departamento_ver',
            'nube_departamento_subir',
            'nube_departamento_descargar',
            'nube_departamento_renombrar',
            'nube_departamento_mover',
            'nube_departamento_eliminar',
            'nube_departamento_publicar',
        ])->get(route('folders.department'))
            ->assertOk()
            ->assertSee('acciones.pdf')
            ->assertSee('Colaborativo')
            ->assertSee('Colaborativo (actual)')
            ->assertSee('Privado')
            ->assertSee('Público')
            ->assertSee('Subir archivo')
            ->assertSee('Cambiar clasificación')
            ->assertSee(route('files.download', $file))
            ->assertSee(route('files.visibility', $file));
    }

    private function storedFile(
        User $owner,
        FileVisibility $visibility,
        string $displayName,
        ?Folder $folder = null,
    ): File {
        $file = File::factory()->create([
            'folder_id' => $folder?->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => $displayName,
            'original_name' => $displayName,
            'visibility' => $visibility,
        ]);

        Storage::disk('nube')->put($file->path, 'contenido');

        return $file;
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
