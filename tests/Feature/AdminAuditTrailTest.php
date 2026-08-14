<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_trail_lists_events_with_actor_resource_and_origin(): void
    {
        $superuser = $this->superuser();
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $actor = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
        ]);
        $file = File::factory()->create([
            'department_id' => $department->id,
            'display_name' => 'Informe auditado.pdf',
        ]);

        $this->log($actor, 'file.downloaded', File::class, $file->id, '10.0.0.5');
        $this->log($superuser, 'admin.file.downloaded', File::class, $file->id, '10.0.0.9');

        $this->authenticated($superuser)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('file.downloaded')
            ->assertSee('admin.file.downloaded')
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('Obras Públicas')
            ->assertSee('Informe auditado.pdf')
            ->assertSee('10.0.0.5')
            ->assertSee('Administrativa')
            ->assertSee('De usuario');
    }

    public function test_audit_trail_filters_by_actor_department_action_resource_ip_and_dates(): void
    {
        $superuser = $this->superuser();
        $technology = Department::factory()->create(['name' => 'Tecnología']);
        $treasury = Department::factory()->create(['name' => 'Tesorería']);
        $ana = User::factory()->create(['department_id' => $technology->id, 'email' => 'ana@example.test']);
        $bruno = User::factory()->create(['department_id' => $treasury->id, 'email' => 'bruno@example.test']);
        $file = File::factory()->create();
        $folder = Folder::factory()->create();

        $upload = $this->log($ana, 'file.uploaded', File::class, $file->id, '10.0.0.5', '2026-08-10 09:00:00');
        $creation = $this->log($bruno, 'folder.created', Folder::class, $folder->id, '192.168.1.20', '2026-06-01 09:00:00');
        $this->log($superuser, 'admin.trash.file_purged', File::class, $file->id, '10.0.0.9', '2026-08-11 09:00:00');

        // El selector de acciones enumera todas las claves existentes, así que
        // el filtrado se comprueba sobre el enlace de detalle de cada evento.
        $filters = [
            ['user_id' => $ana->id],
            ['action' => 'file.uploaded'],
            ['resource_type' => File::class],
            ['ip' => '10.0.0.5'],
            ['date_from' => '2026-08-01'],
        ];

        foreach ($filters as $filter) {
            $this->authenticated($superuser)
                ->get(route('admin.audit', $filter))
                ->assertOk()
                ->assertSee(route('admin.audit.show', $upload), false)
                ->assertDontSee(route('admin.audit.show', $creation), false);
        }

        $this->authenticated($superuser)
            ->get(route('admin.audit', ['department_id' => $treasury->id]))
            ->assertOk()
            ->assertSee(route('admin.audit.show', $creation), false)
            ->assertDontSee(route('admin.audit.show', $upload), false);

        $this->authenticated($superuser)
            ->get(route('admin.audit', ['date_to' => '2026-07-01']))
            ->assertOk()
            ->assertSee(route('admin.audit.show', $creation), false)
            ->assertDontSee(route('admin.audit.show', $upload), false);
    }

    public function test_audit_trail_separates_administrative_actions_from_user_actions(): void
    {
        $superuser = $this->superuser();
        $actor = User::factory()->create();
        $file = File::factory()->create();

        $userEvent = $this->log($actor, 'file.deleted', File::class, $file->id);
        $adminEvent = $this->log($superuser, 'admin.trash.file_restored', File::class, $file->id);

        $this->authenticated($superuser)
            ->get(route('admin.audit', ['scope' => 'administrative']))
            ->assertOk()
            ->assertSee('Administrativa')
            ->assertSee(route('admin.audit.show', $adminEvent), false)
            ->assertDontSee(route('admin.audit.show', $userEvent), false);

        $this->authenticated($superuser)
            ->get(route('admin.audit', ['scope' => 'user']))
            ->assertOk()
            ->assertSee('De usuario')
            ->assertSee(route('admin.audit.show', $userEvent), false)
            ->assertDontSee(route('admin.audit.show', $adminEvent), false);
    }

    public function test_event_detail_shows_context_without_physical_paths_or_secrets(): void
    {
        $superuser = $this->superuser();
        $actor = User::factory()->create([
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
        ]);
        $file = File::factory()->create(['display_name' => 'Contrato.pdf']);

        $log = AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'file.moved',
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'NavegadorDePrueba/1.0',
            'details' => [
                'display_name' => 'Contrato.pdf',
                'path' => 'departamentos/ruta-que-no-debe-mostrarse.pdf',
                'stored_name' => 'nombre-fisico-oculto.pdf',
                'checksum' => str_repeat('a', 64),
                'access_token' => 'token-secreto-de-prueba',
                'changes' => [
                    'folder_id' => ['before' => null, 'after' => 'destino'],
                    'path' => 'otra/ruta/interna.pdf',
                ],
            ],
            'created_at' => now(),
        ]);

        $previous = $this->log($actor, 'file.uploaded', File::class, $file->id);

        $this->authenticated($superuser)
            ->get(route('admin.audit.show', $log))
            ->assertOk()
            ->assertSee('file.moved')
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('10.0.0.5')
            ->assertSee('NavegadorDePrueba/1.0')
            ->assertSee('Contrato.pdf')
            ->assertSee('folder_id')
            ->assertSee('[OCULTO]')
            ->assertDontSee('departamentos/ruta-que-no-debe-mostrarse.pdf')
            ->assertDontSee('nombre-fisico-oculto.pdf')
            ->assertDontSee(str_repeat('a', 64))
            ->assertDontSee('token-secreto-de-prueba')
            ->assertDontSee('otra/ruta/interna.pdf')
            ->assertSee(route('admin.audit.show', $previous), false)
            ->assertSee(route('admin.users.show', $actor), false);
    }

    public function test_audit_trail_is_reserved_for_superusers_and_stays_read_only(): void
    {
        $superuser = $this->superuser();
        $regularUser = User::factory()->create();
        $log = $this->log($regularUser, 'auth.login');

        $this->get(route('admin.audit'))->assertRedirect(route('login'));

        $this->authenticated($regularUser, superuser: false)
            ->get(route('admin.audit'))
            ->assertForbidden();

        $this->authenticated($superuser)
            ->post('/admin/auditoria', ['action' => 'inventado'])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->patch(route('admin.audit.show', $log), ['action' => 'alterado'])
            ->assertMethodNotAllowed();

        $this->authenticated($superuser)
            ->delete(route('admin.audit.show', $log))
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'auth.login',
        ]);
    }

    private function log(
        User $actor,
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?string $ip = null,
        ?string $createdAt = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $ip,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function superuser(): User
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
        $user->unsetRelation('roles');

        return $user;
    }

    private function authenticated(User $user, bool $superuser = true): static
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'nube_inicio_ver'],
            ['display_name' => 'nube_inicio_ver'],
        );
        $user->permissions()->syncWithoutDetaching([
            $permission->id => ['created_at' => now()],
        ]);
        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver'],
            'access.roles' => $superuser ? ['superuser'] : [],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
