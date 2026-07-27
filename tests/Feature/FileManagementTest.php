<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class FileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_file_can_be_uploaded_with_safe_metadata_checksum_and_audit(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $folder = $this->folder($user, 'Contratos');

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'contrato.pdf',
                    512,
                    'application/pdf',
                ),
                'folder_id' => $folder->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file = File::query()->firstOrFail();

        $this->assertSame($user->id, $file->owner_id);
        $this->assertSame($user->department_id, $file->department_id);
        $this->assertSame($folder->id, $file->folder_id);
        $this->assertSame('contrato.pdf', $file->original_name);
        $this->assertSame('contrato.pdf', $file->display_name);
        $this->assertSame(FileVisibility::Private, $file->visibility);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.pdf$/', $file->stored_name);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $file->checksum);
        $this->assertStringNotContainsString('contrato.pdf', $file->path);
        Storage::disk('nube')->assertExists($file->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.uploaded',
            'resource_id' => $file->id,
        ]);
    }

    public function test_upload_limit_is_exactly_200_mb_and_invalid_types_are_rejected(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->assertSame(204800, config('nube.files.max_size_kb'));

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'limite-exacto.pdf',
                    204800,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $acceptedFile = File::query()->firstOrFail();
        Storage::disk('nube')->delete($acceptedFile->path);
        $acceptedFile->forceDelete();

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'demasiado-grande.pdf',
                    204801,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('file', errorBag: 'uploadFile');

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'programa.exe',
                    10,
                    'application/x-msdownload',
                ),
            ])
            ->assertSessionHasErrors('file', errorBag: 'uploadFile');

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create(
                    'mime-falso.pdf',
                    10,
                    'application/x-msdownload',
                ),
            ])
            ->assertSessionHasErrors('file', errorBag: 'uploadFile');

        $this->assertSame(0, File::query()->count());
        $this->assertSame([], Storage::disk('nube')->allFiles());
    }

    public function test_file_cannot_be_uploaded_to_another_users_folder(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignFolder = $this->folder($other, 'Ajena');

        $this->authenticated($user, ['nube_mis_archivos_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('privado.pdf', 10, 'application/pdf'),
                'folder_id' => $foreignFolder->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, File::query()->count());
        $this->assertSame([], Storage::disk('nube')->allFiles());
    }

    public function test_database_failure_removes_the_physical_file(): void
    {
        Storage::fake('nube');
        $departmentOwner = User::factory()->make();
        $departmentOwner->id = 999999;
        $departmentOwner->department_id = 999999;

        try {
            app(FileStorageService::class)->upload(
                UploadedFile::fake()->create('fallo.pdf', 10, 'application/pdf'),
                $departmentOwner,
                null,
            );

            $this->fail('La creación debía fallar por la llave foránea.');
        } catch (Throwable) {
            $this->assertSame(0, File::query()->count());
            $this->assertSame([], Storage::disk('nube')->allFiles());
        }
    }

    public function test_storage_failure_does_not_create_database_record(): void
    {
        $invalidRoot = tempnam(sys_get_temp_dir(), 'nube-storage-root-');
        config([
            'filesystems.disks.nube' => [
                'driver' => 'local',
                'root' => $invalidRoot,
                'throw' => true,
            ],
        ]);
        Storage::forgetDisk('nube');
        $user = User::factory()->create();

        try {
            app(FileStorageService::class)->upload(
                UploadedFile::fake()->create('fallo.pdf', 10, 'application/pdf'),
                $user,
                null,
            );

            $this->fail('El almacenamiento debía rechazar una raíz que es un archivo.');
        } catch (Throwable) {
            $this->assertSame(0, File::query()->count());
        } finally {
            if (is_string($invalidRoot) && file_exists($invalidRoot)) {
                unlink($invalidRoot);
            }
        }
    }

    public function test_owner_can_download_existing_file_and_download_is_audited(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'reporte.pdf');

        $this->authenticated($user, ['nube_mis_archivos_descargar'])
            ->get(route('files.download', $file))
            ->assertOk()
            ->assertDownload('reporte.pdf');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.downloaded',
            'resource_id' => $file->id,
        ]);
    }

    public function test_private_file_cannot_be_downloaded_by_another_user_or_when_missing(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = $this->storedFile($owner, 'privado.pdf');

        $this->authenticated($other, ['nube_mis_archivos_descargar'])
            ->get(route('files.download', $file))
            ->assertForbidden();

        Storage::disk('nube')->delete($file->path);

        $this->authenticated($owner, ['nube_mis_archivos_descargar'])
            ->get(route('files.download', $file))
            ->assertNotFound();
    }

    public function test_rename_changes_only_display_name_and_is_audited(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'borrador.pdf');
        $storedName = $file->stored_name;
        $path = $file->path;

        $this->authenticated($user, ['nube_mis_archivos_renombrar'])
            ->patch(route('files.update', $file), [
                'display_name' => 'Contrato final.pdf',
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file->refresh();
        $this->assertSame('Contrato final.pdf', $file->display_name);
        $this->assertSame($storedName, $file->stored_name);
        $this->assertSame($path, $file->path);
        Storage::disk('nube')->assertExists($path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.renamed',
            'resource_id' => $file->id,
        ]);
    }

    public function test_file_name_must_be_valid_and_unique_in_the_same_folder(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $first = $this->storedFile($user, 'Contrato.pdf');
        $second = $this->storedFile($user, 'Borrador.pdf');

        $this->authenticated($user, ['nube_mis_archivos_renombrar'])
            ->patch(route('files.update', $second), [
                'display_name' => 'CONTRATO.PDF',
                'file_context' => $second->id,
            ])
            ->assertSessionHasErrors('display_name', errorBag: 'renameFile');

        $this->authenticated($user, ['nube_mis_archivos_renombrar'])
            ->patch(route('files.update', $second), [
                'display_name' => '../invalido.pdf',
                'file_context' => $second->id,
            ])
            ->assertSessionHasErrors('display_name', errorBag: 'renameFile');

        $this->assertSame('Contrato.pdf', $first->fresh()->display_name);
        $this->assertSame('Borrador.pdf', $second->fresh()->display_name);
    }

    public function test_file_can_be_moved_between_owned_private_folders(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $origin = $this->folder($user, 'Origen');
        $destination = $this->folder($user, 'Destino');
        $file = $this->storedFile($user, 'mover.pdf', $origin);
        $oldPath = $file->path;

        $this->authenticated($user, ['nube_mis_archivos_mover'])
            ->patch(route('files.move', $file), [
                'destination_folder_id' => $destination->id,
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file->refresh();
        $this->assertSame($destination->id, $file->folder_id);
        $this->assertNotSame($oldPath, $file->path);
        Storage::disk('nube')->assertMissing($oldPath);
        Storage::disk('nube')->assertExists($file->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.moved',
            'resource_id' => $file->id,
        ]);
    }

    public function test_file_cannot_be_moved_to_foreign_folder_and_missing_physical_file_rolls_back(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $origin = $this->folder($user, 'Origen');
        $ownedDestination = $this->folder($user, 'Destino');
        $foreignDestination = $this->folder($other, 'Ajena');
        $file = $this->storedFile($user, 'mover.pdf', $origin);

        $this->authenticated($user, ['nube_mis_archivos_mover'])
            ->patch(route('files.move', $file), [
                'destination_folder_id' => $foreignDestination->id,
                'file_context' => $file->id,
            ])
            ->assertForbidden();

        Storage::disk('nube')->delete($file->path);

        $this->authenticated($user, ['nube_mis_archivos_mover'])
            ->patch(route('files.move', $file), [
                'destination_folder_id' => $ownedDestination->id,
                'file_context' => $file->id,
            ])
            ->assertSessionHas('file_error');

        $this->assertSame($origin->id, $file->fresh()->folder_id);
    }

    public function test_delete_moves_file_to_trash_and_restore_returns_it_to_private_storage(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $folder = $this->folder($user, 'Restaurados');
        $file = $this->storedFile($user, 'temporal.pdf');
        $oldPath = $file->path;

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('files.destroy', $file))
            ->assertRedirect()
            ->assertSessionHas('status');

        $trashed = File::onlyTrashed()->findOrFail($file->id);
        $this->assertStringStartsWith('papelera/usuarios/', $trashed->path);
        Storage::disk('nube')->assertMissing($oldPath);
        Storage::disk('nube')->assertExists($trashed->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.deleted',
            'resource_id' => $file->id,
        ]);

        $this->authenticated($user, ['nube_mis_archivos_descargar'])
            ->get(route('files.download', $file))
            ->assertNotFound();

        $this->authenticated($user, ['nube_papelera_restaurar'])
            ->post(route('files.restore', $file->id), [
                'destination_folder_id' => $folder->id,
                'file_context' => $file->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $restored = File::query()->findOrFail($file->id);
        $this->assertSame($folder->id, $restored->folder_id);
        $this->assertStringContainsString("/carpetas/{$folder->id}/", $restored->path);
        Storage::disk('nube')->assertExists($restored->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'file.restored',
            'resource_id' => $file->id,
        ]);
    }

    public function test_another_user_cannot_delete_or_restore_private_file(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = $this->storedFile($owner, 'privado.pdf');

        $this->authenticated($other, ['nube_mis_archivos_eliminar'])
            ->delete(route('files.destroy', $file))
            ->assertForbidden();

        $this->authenticated($owner, ['nube_mis_archivos_eliminar'])
            ->delete(route('files.destroy', $file))
            ->assertRedirect();

        $this->authenticated($other, ['nube_papelera_restaurar'])
            ->post(route('files.restore', $file->id), [
                'destination_folder_id' => null,
                'file_context' => $file->id,
            ])
            ->assertForbidden();

        $this->assertNotNull(File::onlyTrashed()->find($file->id));
    }

    public function test_explorer_renders_upload_and_private_file_actions_according_to_permissions(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'acciones.pdf');

        $this->authenticated($user, [
            'nube_mis_archivos_ver',
            'nube_mis_archivos_subir',
            'nube_mis_archivos_descargar',
            'nube_mis_archivos_renombrar',
            'nube_mis_archivos_mover',
            'nube_mis_archivos_eliminar',
        ])->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('acciones.pdf')
            ->assertSee('Subir archivo')
            ->assertSee('Máximo: 200 MB')
            ->assertSee(route('files.download', $file))
            ->assertSee('Renombrar archivo')
            ->assertSee('Mover archivo')
            ->assertSee('Eliminar archivo');
    }

    public function test_trash_renders_restore_action_for_deleted_private_file(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'restaurable.pdf');

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('files.destroy', $file))
            ->assertRedirect();

        $this->authenticated($user, [
            'nube_papelera_ver',
            'nube_papelera_restaurar',
        ])->get(route('folders.trash'))
            ->assertOk()
            ->assertSee('restaurable.pdf')
            ->assertSee('Restaurar archivo')
            ->assertSee(route('files.restore', $file->id));
    }

    public function test_trash_shows_retention_notice_deadline_and_permanent_delete_action(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'caducable.pdf');
        app(FileStorageService::class)->delete($file->load('owner'));

        $this->authenticated($user, [
            'nube_papelera_ver',
            'nube_papelera_restaurar',
            'nube_mis_archivos_eliminar',
        ])->get(route('folders.trash'))
            ->assertOk()
            ->assertSee('se eliminan permanentemente 30 días')
            ->assertSee('Se elimina en 30 día(s)')
            ->assertSee('Eliminar permanentemente')
            ->assertSee('data-permanent-delete-form', false)
            ->assertSee(route('files.force-destroy', $file->id));
    }

    public function test_owner_can_permanently_delete_a_trashed_file_and_action_is_audited(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = $this->storedFile($user, 'definitivo.pdf');
        $trashed = app(FileStorageService::class)->delete($file->load('owner'));
        $trashPath = $trashed->path;

        $this->authenticated($user, ['nube_mis_archivos_eliminar'])
            ->delete(route('files.force-destroy', $file->id))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNull(File::withTrashed()->find($file->id));
        Storage::disk('nube')->assertMissing($trashPath);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'file.permanently_deleted',
            'resource_id' => $file->id,
        ]);
    }

    public function test_daily_purge_removes_only_files_trashed_for_at_least_30_days(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $expired = app(FileStorageService::class)->delete(
            $this->storedFile($user, 'vencido.pdf')->load('owner'),
        );
        $recent = app(FileStorageService::class)->delete(
            $this->storedFile($user, 'reciente.pdf')->load('owner'),
        );

        DB::table('files')->where('id', $expired->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
        DB::table('files')->where('id', $recent->id)->update([
            'deleted_at' => now()->subDays(29),
        ]);

        $this->artisan('files:purge-trash')
            ->expectsOutput('Purga finalizada: 1 archivo(s) eliminado(s), 0 error(es).')
            ->assertSuccessful();

        $this->assertNull(File::withTrashed()->find($expired->id));
        $this->assertNotNull(File::onlyTrashed()->find($recent->id));
        Storage::disk('nube')->assertMissing($expired->path);
        Storage::disk('nube')->assertExists($recent->path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'file.permanently_deleted',
            'resource_id' => $expired->id,
        ]);
    }

    public function test_file_model_mutations_are_audited_outside_controllers(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $this->authenticated($user, []);
        $file = $this->storedFile($user, 'interno.pdf');

        $file->update(['size_bytes' => 4096]);
        $file->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'file.uploaded',
            'resource_id' => $file->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'file.updated',
            'resource_id' => $file->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'file.deleted',
            'resource_id' => $file->id,
        ]);
    }

    private function folder(User $owner, string $name): Folder
    {
        return Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => $name,
            'visibility' => FileVisibility::Private,
            'path_cache' => "/{$name}",
        ]);
    }

    private function storedFile(
        User $owner,
        string $displayName,
        ?Folder $folder = null,
    ): File {
        $file = File::factory()->create([
            'folder_id' => $folder?->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => $displayName,
            'original_name' => $displayName,
            'visibility' => FileVisibility::Private,
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
