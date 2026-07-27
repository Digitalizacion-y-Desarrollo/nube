<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\User;
use App\Services\Access\DepartmentCollaboratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DepartmentCollaboratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_integration_endpoint_and_only_returns_active_users_from_the_department(): void
    {
        config([
            'services.access.url' => 'https://access.example.test',
            'services.access.system_key' => 'configured-system-key',
        ]);

        $department = Department::factory()->create([
            'name' => 'Tecnologías de la Información',
        ]);
        $currentUser = User::factory()->create([
            'external_id' => '1',
            'department_id' => $department->id,
        ]);

        Http::fake([
            'https://access.example.test/api/integrations/users*' => Http::response([
                'data' => [
                    $this->remoteUser(1, 'Actual', $currentUser->email),
                    $this->remoteUser(2, 'Ana', 'ana@example.test'),
                    $this->remoteUser(
                        3,
                        'Inactiva',
                        'inactiva@example.test',
                        active: false,
                    ),
                    $this->remoteUser(
                        4,
                        'Externa',
                        'externa@example.test',
                        department: 'Tesorería',
                    ),
                ],
            ]),
        ]);

        $users = app(DepartmentCollaboratorService::class)
            ->for($currentUser, 'current-token');

        $this->assertCount(1, $users);
        $this->assertSame('Ana Pérez López', trim(
            "{$users[0]['name']} {$users[0]['last_name']}",
        ));
        $this->assertSame('Analista', $users[0]['position']);
        $this->assertSame('Colaborador', $users[0]['role']);
        $this->assertDatabaseHas('users', [
            'external_id' => '2',
            'department_id' => $department->id,
            'email' => 'ana@example.test',
            'active' => true,
        ]);
        $this->assertDatabaseMissing('users', ['external_id' => '3']);
        $this->assertDatabaseMissing('users', ['external_id' => '4']);

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://access.example.test/api/integrations/users?system_key=configured-system-key'
            && $request->hasHeader('Authorization', 'Bearer current-token')
            && $request->hasHeader('Accept', 'application/json'));
    }

    /**
     * @return array<string, mixed>
     */
    private function remoteUser(
        int $id,
        string $name,
        string $email,
        bool $active = true,
        string $department = 'tecnologias de la informacion',
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
            'email' => $email,
            'cargo' => 'Analista',
            'departamento' => $department,
            'rol' => 'Colaborador',
            'permisos' => 'nube_inicio_ver',
            'activo' => $active,
        ];
    }
}
