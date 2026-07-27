<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExplorerSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mine_only_shows_the_authenticated_users_private_root_content(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'name' => 'Carpeta privada propia',
            'visibility' => FileVisibility::Private,
        ]);
        Folder::factory()->create([
            'owner_id' => $otherUser->id,
            'department_id' => $otherUser->department_id,
            'name' => 'Carpeta privada ajena',
            'visibility' => FileVisibility::Private,
        ]);

        $this->authenticated($user, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('Carpeta privada propia')
            ->assertDontSee('Carpeta privada ajena');
    }

    public function test_department_only_shows_collaborative_content_from_the_users_department(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        $owner = User::factory()->create(['department_id' => $department->id]);
        $outsideOwner = User::factory()->create(['department_id' => $otherDepartment->id]);

        File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'display_name' => 'Colaborativo del área.pdf',
            'visibility' => FileVisibility::Collaborative,
        ]);
        File::factory()->create([
            'owner_id' => $outsideOwner->id,
            'department_id' => $otherDepartment->id,
            'display_name' => 'Colaborativo externo.pdf',
            'visibility' => FileVisibility::Collaborative,
        ]);

        $this->authenticated($user, ['nube_departamento_ver'])
            ->get(route('folders.department'))
            ->assertOk()
            ->assertSee('Colaborativo del área.pdf')
            ->assertDontSee('Colaborativo externo.pdf');
    }

    public function test_public_section_shows_public_content_from_any_department(): void
    {
        $user = User::factory()->create();
        $publisher = User::factory()->create();

        File::factory()->create([
            'owner_id' => $publisher->id,
            'department_id' => $publisher->department_id,
            'display_name' => 'Manual público.pdf',
            'visibility' => FileVisibility::Public,
        ]);
        File::factory()->create([
            'owner_id' => $publisher->id,
            'department_id' => $publisher->department_id,
            'display_name' => 'Documento privado.pdf',
            'visibility' => FileVisibility::Private,
        ]);

        $this->authenticated($user, ['nube_publicos_ver'])
            ->get(route('folders.public'))
            ->assertOk()
            ->assertSee('Manual público.pdf')
            ->assertDontSee('Documento privado.pdf');
    }

    public function test_trash_only_shows_the_authenticated_users_deleted_content(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $deletedFolder = Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'name' => 'Eliminado propio',
        ]);
        $deletedFolder->delete();

        $otherDeletedFolder = Folder::factory()->create([
            'owner_id' => $otherUser->id,
            'department_id' => $otherUser->department_id,
            'name' => 'Eliminado ajeno',
        ]);
        $otherDeletedFolder->delete();

        Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'name' => 'Carpeta vigente',
        ]);

        $this->authenticated($user, ['nube_papelera_ver'])
            ->get(route('folders.trash'))
            ->assertOk()
            ->assertSee('Eliminado propio')
            ->assertDontSee('Eliminado ajeno')
            ->assertDontSee('Carpeta vigente');
    }

    public function test_section_requires_its_specific_view_permission(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user)
            ->get(route('folders.mine'))
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions = []): static
    {
        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => array_merge(['nube_inicio_ver'], $permissions),
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
