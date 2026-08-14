<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpicSevenInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_files_folders_counts_and_activity(): void
    {
        $user = User::factory()->create(['name' => 'María']);
        $this->givePermissions($user, [
            'nube_inicio_ver',
            'nube_mis_archivos_ver',
            'nube_mis_archivos_descargar',
        ]);
        $folder = Folder::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'name' => 'Expedientes reales',
            'visibility' => FileVisibility::Private,
        ]);
        $file = File::factory()->create([
            'folder_id' => $folder->id,
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'display_name' => 'Informe real.pdf',
            'visibility' => FileVisibility::Private,
        ]);
        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'file.downloaded',
            'resource_type' => File::class,
            'resource_id' => $file->id,
            'details' => ['display_name' => $file->display_name],
            'created_at' => now(),
        ]);

        $this->authenticated($user, [
            'nube_inicio_ver',
            'nube_mis_archivos_ver',
            'nube_mis_archivos_descargar',
        ])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Informe real.pdf')
            ->assertSee('Expedientes reales')
            ->assertSee('Descargaste Informe real.pdf')
            ->assertSee(now()->translatedFormat('j \d\e F \d\e Y'))
            ->assertDontSee('Contrato_2025_Final.docx');
    }

    public function test_explorer_searches_and_filters_by_type_owner_visibility_and_date(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create(['department_id' => $user->department_id]);
        $this->givePermissions($user, ['nube_publicos_ver']);

        File::factory()->create([
            'owner_id' => $otherOwner->id,
            'department_id' => $otherOwner->department_id,
            'display_name' => 'Reporte buscado.pdf',
            'visibility' => FileVisibility::Public,
            'uploaded_at' => now(),
        ]);
        File::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'display_name' => 'Reporte antiguo.pdf',
            'visibility' => FileVisibility::Public,
            'uploaded_at' => now()->subMonth(),
        ]);
        Folder::factory()->create([
            'owner_id' => $otherOwner->id,
            'department_id' => $otherOwner->department_id,
            'name' => 'Reporte en carpeta',
            'visibility' => FileVisibility::Public,
        ]);

        $this->authenticated($user, ['nube_publicos_ver'])
            ->get(route('folders.public', [
                'q' => 'buscado',
                'type' => 'file',
                'visibility' => 'public',
                'owner_id' => $otherOwner->id,
                'date_from' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Reporte buscado.pdf')
            ->assertDontSee('Reporte antiguo.pdf')
            ->assertDontSee('Reporte en carpeta')
            ->assertSee('1 de 3 elementos');
    }

    public function test_explorer_sorts_by_size_and_paginates_while_preserving_filters(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $index) {
            File::factory()->create([
                'owner_id' => $user->id,
                'department_id' => $user->department_id,
                'display_name' => sprintf('Documento %02d.pdf', $index),
                'size_bytes' => $index * 1000,
                'visibility' => FileVisibility::Private,
            ]);
        }

        $this->authenticated($user, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine', [
                'type' => 'file',
                'sort' => 'size',
                'direction' => 'desc',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Documento 12.pdf', 'Documento 11.pdf'])
            ->assertDontSee('Documento 01.pdf')
            ->assertSee('page=2', false)
            ->assertSee('type=file', false);

        $this->authenticated($user, ['nube_mis_archivos_ver'])
            ->get(route('folders.mine', [
                'type' => 'file',
                'sort' => 'size',
                'direction' => 'desc',
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Documento 02.pdf')
            ->assertSee('Documento 01.pdf')
            ->assertDontSee('Documento 12.pdf');
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
            'access.permissions' => array_values(array_unique([
                'nube_inicio_ver',
                ...$permissions,
            ])),
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
