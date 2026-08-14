<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Http\Middleware\EnsureAdministrativePermission;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cierra los huecos de cobertura del Épico 20: comportamiento de la sección
 * administrativa ante un fallo del API de Accesos y ante la ausencia del
 * archivo físico en cada operación que lo toca.
 */
class AdminStabilizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.access', [
            'url' => 'https://access.example.test',
            'system_key' => 'test-system-key',
            'timeout' => 5,
            'session_check_interval' => 300,
        ]);
    }

    public function test_administrative_sections_end_the_session_when_the_access_api_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['message' => 'Token expirado.'], 401)]);

        $superuser = $this->superuser(withAdministrationPermission: true);

        $routes = [
            route('admin.dashboard'),
            route('admin.files'),
            route('admin.trash'),
            route('admin.audit'),
            route('admin.users'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($superuser)
                ->withSession([
                    'access.token' => 'expired-token',
                    'access.permissions' => [
                        'nube_inicio_ver',
                        EnsureAdministrativePermission::PERMISSION,
                    ],
                    'access.roles' => ['superuser'],
                    // Fuerza la revalidación en la siguiente petición.
                    'access.validated_at' => now()->subHour()->timestamp,
                ])
                ->get($url)
                ->assertRedirect(route('login'))
                ->assertSessionHas('auth_error');

            $this->assertGuest();
        }
    }

    public function test_administrative_write_routes_also_close_when_the_api_is_unreachable(): void
    {
        Storage::fake('nube');
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException('sin red'));

        $superuser = $this->superuser(withAdministrationPermission: true);
        $file = $this->fileWithContent('Operable.pdf');

        $this->actingAs($superuser)
            ->withSession([
                'access.token' => 'token-valido',
                'access.permissions' => [
                    'nube_inicio_ver',
                    EnsureAdministrativePermission::PERMISSION,
                ],
                'access.roles' => ['superuser'],
                'access.validated_at' => now()->subHour()->timestamp,
            ])
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);

        // El observador registra `file.uploaded` al crear el modelo en la
        // preparación; lo que no debe existir es ninguna acción administrativa.
        $this->assertSame(
            0,
            AuditLog::query()->where('action', 'like', 'admin.%')->count(),
        );
    }

    public function test_administrative_download_fails_safely_when_the_physical_file_is_missing(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);

        // El registro existe, pero nunca se escribió el archivo en el disco.
        $file = File::factory()->create([
            'display_name' => 'Sin copia fisica.pdf',
            'disk' => 'nube',
            'path' => 'temporales/ruta-que-no-debe-mostrarse.pdf',
        ]);

        $this->authenticated($superuser)
            ->get(route('admin.files.download', $file))
            ->assertNotFound();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'admin.file.downloaded',
            'resource_id' => $file->id,
        ]);
    }

    public function test_administrative_reclassification_reports_a_neutral_error_when_the_physical_file_is_missing(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Sin copia fisica.pdf',
            'disk' => 'nube',
            'path' => 'temporales/ruta-que-no-debe-mostrarse.pdf',
            'visibility' => FileVisibility::Private,
        ]);

        $response = $this->authenticated($superuser)
            ->from(route('admin.files'))
            ->patch(route('admin.files.visibility', $file), [
                'file_context' => $file->id,
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect(route('admin.files'))
            ->assertSessionHas('admin_file_error');

        $message = (string) $response->getSession()->get('admin_file_error');

        $this->assertStringNotContainsString('temporales/ruta-que-no-debe-mostrarse.pdf', $message);
        $this->assertStringNotContainsString(base_path(), $message);

        $file->refresh();
        $this->assertSame(FileVisibility::Private, $file->visibility);
        $this->assertSame('temporales/ruta-que-no-debe-mostrarse.pdf', $file->path);
    }

    public function test_sending_to_trash_reports_a_neutral_error_when_the_physical_file_is_missing(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);

        $file = File::factory()->create([
            'display_name' => 'Sin copia fisica.pdf',
            'disk' => 'nube',
            'path' => 'temporales/ruta-que-no-debe-mostrarse.pdf',
        ]);

        $response = $this->authenticated($superuser)
            ->from(route('admin.files'))
            ->delete(route('admin.files.destroy', $file))
            ->assertRedirect(route('admin.files'))
            ->assertSessionHas('admin_file_error');

        $message = (string) $response->getSession()->get('admin_file_error');

        $this->assertStringNotContainsString('temporales/ruta-que-no-debe-mostrarse.pdf', $message);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    public function test_permanent_deletion_completes_when_the_physical_copy_is_already_gone(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);

        $file = File::factory()->create([
            'display_name' => 'Huerfano.pdf',
            'disk' => 'nube',
            'path' => 'papelera/huerfano.pdf',
        ]);
        $file->delete();

        // La copia física ya no está: la purga debe limpiar el registro igual,
        // porque dejarlo huérfano perpetuaría la inconsistencia.
        Storage::disk('nube')->assertMissing('papelera/huerfano.pdf');

        $this->authenticated($superuser)
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Huerfano.pdf',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.trash.file_purged',
            'resource_id' => $file->id,
            'user_id' => $superuser->id,
        ]);
    }

    public function test_restoring_a_file_without_its_physical_copy_leaves_the_record_untouched(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);

        $file = File::factory()->create([
            'display_name' => 'Irrecuperable.pdf',
            'disk' => 'nube',
            'path' => 'papelera/irrecuperable.pdf',
        ]);
        $file->delete();

        $this->authenticated($superuser)
            ->from(route('admin.trash'))
            ->post(route('admin.trash.files.restore', $file->id))
            ->assertRedirect(route('admin.trash'))
            ->assertSessionHas('admin_trash_error');

        $file->refresh();
        $this->assertTrue($file->trashed());
        $this->assertSame('papelera/irrecuperable.pdf', $file->path);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'admin.trash.file_restored',
            'resource_id' => $file->id,
        ]);
    }

    public function test_audit_trail_survives_the_deletion_of_the_resource_it_describes(): void
    {
        Storage::fake('nube');
        $superuser = $this->superuser(withAdministrationPermission: true);
        $file = $this->fileWithContent('Rastreable.pdf', 'papelera/rastreable.pdf');
        $file->delete();

        $this->authenticated($superuser)
            ->delete(route('admin.trash.files.purge', $file->id), [
                'confirmation' => 'Rastreable.pdf',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);

        $log = AuditLog::query()
            ->where('action', 'admin.trash.file_purged')
            ->where('resource_id', $file->id)
            ->firstOrFail();

        // El evento conserva el contexto aunque el recurso ya no exista.
        $this->authenticated($superuser)
            ->get(route('admin.audit.show', $log))
            ->assertOk()
            ->assertSee('admin.trash.file_purged')
            ->assertSee('Rastreable.pdf');
    }

    private function fileWithContent(
        string $displayName,
        string $path = 'temporales/archivo.pdf',
    ): File {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);

        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => $displayName,
            'disk' => 'nube',
            'path' => $path,
            'visibility' => FileVisibility::Private,
        ]);
        Storage::disk('nube')->put($path, 'contenido');

        return $file;
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

    private function authenticated(User $user): static
    {
        $permissionNames = [
            'nube_inicio_ver',
            EnsureAdministrativePermission::PERMISSION,
        ];

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
