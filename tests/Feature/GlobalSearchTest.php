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
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_only_returns_authorized_files_and_folders(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['department_id' => $department->id]);
        $outsider = User::factory()->create(['department_id' => $otherDepartment->id]);
        $permissions = [
            'nube_inicio_ver',
            'nube_mis_archivos_ver',
            'nube_mis_archivos_descargar',
            'nube_departamento_ver',
            'nube_departamento_descargar',
            'nube_publicos_ver',
            'nube_publicos_descargar',
        ];
        $this->givePermissions($user, $permissions);

        Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $department->id,
            'name' => 'Plan privado propio',
            'visibility' => FileVisibility::Private,
        ]);
        File::factory()->create([
            'owner_id' => $colleague->id,
            'department_id' => $department->id,
            'display_name' => 'Plan departamental.pdf',
            'visibility' => FileVisibility::Collaborative,
        ]);
        File::factory()->create([
            'owner_id' => $outsider->id,
            'department_id' => $otherDepartment->id,
            'display_name' => 'Plan público.pdf',
            'visibility' => FileVisibility::Public,
        ]);
        File::factory()->create([
            'owner_id' => $outsider->id,
            'department_id' => $otherDepartment->id,
            'display_name' => 'Plan privado ajeno.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        File::factory()->create([
            'owner_id' => $colleague->id,
            'department_id' => $department->id,
            'display_name' => 'Plan selección privada.pdf',
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
        ]);

        $response = $this->authenticated($user, $permissions)
            ->getJson(route('search', ['q' => 'Plan']));

        $response
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonFragment(['name' => 'Plan privado propio'])
            ->assertJsonFragment(['name' => 'Plan departamental.pdf'])
            ->assertJsonFragment(['name' => 'Plan público.pdf'])
            ->assertJsonMissing(['name' => 'Plan privado ajeno.pdf'])
            ->assertJsonMissing(['name' => 'Plan selección privada.pdf']);
    }

    public function test_global_search_requires_at_least_two_characters(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_inicio_ver'])
            ->getJson(route('search', ['q' => 'a']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_application_shell_renders_the_spotlight_search_controls(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_inicio_ver'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-global-search-open', false)
            ->assertSee('data-global-search', false)
            ->assertSee(route('search'), false)
            ->assertSee('⌘ K');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function givePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $name) {
            $permission = Permission::factory()->create(['name' => $name]);
            $user->permissions()->attach($permission, ['created_at' => now()]);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions): static
    {
        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => $permissions,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
