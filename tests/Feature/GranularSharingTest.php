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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GranularSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_collaborative_and_public_folders_can_be_created(): void
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $collaborator = User::factory()->create(['department_id' => $department->id]);

        $cases = [
            [
                'name' => 'Privada',
                'visibility' => FileVisibility::Private->value,
                'permission' => 'nube_mis_archivos_crear_carpeta',
            ],
            [
                'name' => 'Departamento',
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Department->value,
                'permission' => 'nube_departamento_crear_carpeta',
            ],
            [
                'name' => 'Seleccionada',
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$collaborator->id],
                'permission' => 'nube_departamento_crear_carpeta',
            ],
            [
                'name' => 'Pública',
                'visibility' => FileVisibility::Public->value,
                'permission' => 'nube_publicos_crear_carpeta',
            ],
        ];

        foreach ($cases as $case) {
            $permission = $case['permission'];
            unset($case['permission']);

            $this->authenticated($owner, [$permission])
                ->post(route('folders.store'), $case)
                ->assertRedirect()
                ->assertSessionHas('status');
        }

        $selectedFolder = Folder::query()->where('name', 'Seleccionada')->firstOrFail();

        $this->assertDatabaseHas('folders', [
            'name' => 'Privada',
            'visibility' => FileVisibility::Private->value,
        ]);
        $this->assertDatabaseHas('folders', [
            'name' => 'Departamento',
            'collaboration_scope' => CollaborationScope::Department->value,
        ]);
        $this->assertDatabaseHas('folders', [
            'name' => 'Pública',
            'visibility' => FileVisibility::Public->value,
        ]);
        $this->assertDatabaseHas('folder_collaborators', [
            'folder_id' => $selectedFolder->id,
            'user_id' => $collaborator->id,
        ]);
    }

    public function test_public_folder_can_contain_files_with_four_independent_access_levels(): void
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $selected = User::factory()->create(['department_id' => $department->id]);
        $unselected = User::factory()->create(['department_id' => $department->id]);
        $outsider = User::factory()->create();
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'name' => 'Declaración 2026',
            'visibility' => FileVisibility::Public,
        ]);

        $public = $this->file($owner, $folder, 'Archivo 1 público.pdf', FileVisibility::Public);
        $selectedFile = $this->file(
            $owner,
            $folder,
            'Archivo 2 seleccionado.pdf',
            FileVisibility::Collaborative,
            CollaborationScope::Selected,
        );
        $selectedFile->collaborators()->attach($selected->id, ['created_at' => now()]);
        $this->file(
            $owner,
            $folder,
            'Archivo 3 departamento.pdf',
            FileVisibility::Collaborative,
            CollaborationScope::Department,
        );
        $this->file($owner, $folder, 'Archivo 4 privado.pdf', FileVisibility::Private);

        $sectionPermissions = ['nube_publicos_ver', 'nube_departamento_ver'];

        $this->authenticated($selected, $sectionPermissions)
            ->get(route('folders.public.show', $folder))
            ->assertOk()
            ->assertSee('Archivo 1 público.pdf')
            ->assertSee('Archivo 2 seleccionado.pdf')
            ->assertSee('Archivo 3 departamento.pdf')
            ->assertDontSee('Archivo 4 privado.pdf');

        $this->authenticated($unselected, $sectionPermissions)
            ->get(route('folders.public.show', $folder))
            ->assertOk()
            ->assertSee('Archivo 1 público.pdf')
            ->assertDontSee('Archivo 2 seleccionado.pdf')
            ->assertSee('Archivo 3 departamento.pdf')
            ->assertDontSee('Archivo 4 privado.pdf');

        $this->authenticated($outsider, $sectionPermissions)
            ->get(route('folders.public.show', $folder))
            ->assertOk()
            ->assertSee('Archivo 1 público.pdf')
            ->assertDontSee('Archivo 2 seleccionado.pdf')
            ->assertDontSee('Archivo 3 departamento.pdf')
            ->assertDontSee('Archivo 4 privado.pdf');

        $this->authenticated($owner, [
            ...$sectionPermissions,
            'nube_mis_archivos_ver',
        ])->get(route('folders.public.show', $folder))
            ->assertOk()
            ->assertSee($public->display_name)
            ->assertSee('Archivo 2 seleccionado.pdf')
            ->assertSee('Archivo 3 departamento.pdf')
            ->assertSee('Archivo 4 privado.pdf');
    }

    public function test_selected_collaborative_file_is_persisted_with_same_department_users(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $first = User::factory()->create(['department_id' => $department->id]);
        $second = User::factory()->create(['department_id' => $department->id]);

        $this->authenticated($owner, ['nube_departamento_subir'])
            ->post(route('files.store'), [
                'file' => UploadedFile::fake()->create('seleccionado.pdf', 10, 'application/pdf'),
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file = File::query()->where('display_name', 'seleccionado.pdf')->firstOrFail();

        $this->assertSame(CollaborationScope::Selected, $file->collaboration_scope);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $file->collaborators()->pluck('users.id')->all(),
        );
    }

    public function test_file_inherits_folder_visibility_scope_and_collaborators_unless_overridden(): void
    {
        Storage::fake('nube');
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $first = User::factory()->create(['department_id' => $department->id]);
        $second = User::factory()->create(['department_id' => $department->id]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Selected,
        ]);
        $folder->collaborators()->sync([
            $first->id => [
                'can_view' => true,
                'can_download' => true,
                'can_rename' => true,
                'can_move' => false,
                'can_delete' => false,
                'created_at' => now(),
            ],
            $second->id => ['created_at' => now()],
        ]);

        Http::fake([
            '*/api/integrations/users*' => Http::response([
                'data' => collect([$first, $second])
                    ->map(fn (User $user): array => [
                        'id' => $user->external_id,
                        'name' => $user->name,
                        'apellido_paterno' => $user->last_name,
                        'email' => $user->email,
                        'departamento' => $department->name,
                    ])
                    ->all(),
            ]),
        ]);

        $response = $this->authenticated($owner, [
            'nube_departamento_ver',
            'nube_departamento_subir',
            'nube_mis_archivos_subir',
        ])->get(route('folders.department.show', $folder));

        $response
            ->assertOk()
            ->assertSee('id="upload-collaborators-list"', false)
            ->assertSee('value="selected" selected', false)
            ->assertSee('La clasificación y los colaboradores se heredan de')
            ->assertSee($first->email)
            ->assertSee($second->email);

        preg_match_all(
            '/data-collaborator-checkbox\s+checked/u',
            $response->getContent(),
            $checkedCollaborators,
        );
        $this->assertCount(2, $checkedCollaborators[0]);

        $this->post(route('files.store'), [
            'file' => UploadedFile::fake()->create(
                'heredado.pdf',
                10,
                'application/pdf',
            ),
            'folder_id' => $folder->id,
        ])->assertRedirect()
            ->assertSessionHas('status');

        $inherited = File::query()
            ->where('display_name', 'heredado.pdf')
            ->firstOrFail();

        $this->assertSame(FileVisibility::Collaborative, $inherited->visibility);
        $this->assertSame(
            CollaborationScope::Selected,
            $inherited->collaboration_scope,
        );
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $inherited->collaborators()->pluck('users.id')->all(),
        );
        $this->assertDatabaseHas('file_collaborators', [
            'file_id' => $inherited->id,
            'user_id' => $first->id,
            'can_view' => true,
            'can_download' => true,
            'can_rename' => true,
            'can_move' => false,
            'can_delete' => false,
        ]);

        $this->post(route('files.store'), [
            'file' => UploadedFile::fake()->create(
                'privado.pdf',
                10,
                'application/pdf',
            ),
            'folder_id' => $folder->id,
            'visibility' => FileVisibility::Private->value,
            'collaborators_configured' => '1',
        ])->assertRedirect()
            ->assertSessionHas('status');

        $overridden = File::query()
            ->where('display_name', 'privado.pdf')
            ->firstOrFail();

        $this->assertSame(FileVisibility::Private, $overridden->visibility);
        $this->assertNull($overridden->collaboration_scope);
        $this->assertSame(0, $overridden->collaborators()->count());
    }

    public function test_collaborators_must_be_active_users_from_the_same_department(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $inactive = User::factory()->inactive()->create([
            'department_id' => $owner->department_id,
        ]);

        foreach ([$outsider, $inactive] as $invalidCollaborator) {
            $this->authenticated($owner, ['nube_departamento_subir'])
                ->post(route('files.store'), [
                    'file' => UploadedFile::fake()->create('invalido.pdf', 10, 'application/pdf'),
                    'visibility' => FileVisibility::Collaborative->value,
                    'collaboration_scope' => CollaborationScope::Selected->value,
                    'collaborators' => [$invalidCollaborator->id],
                ])
                ->assertSessionHasErrors('collaborators.0', errorBag: 'uploadFile');
        }

        $this->assertSame(0, File::query()->count());
    }

    public function test_creation_forms_only_list_active_people_from_the_same_department(): void
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $coworker = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Compañera',
        ]);
        $inactive = User::factory()->inactive()->create([
            'department_id' => $department->id,
            'name' => 'Inactiva',
        ]);
        $outsider = User::factory()->create(['name' => 'Externo']);

        Http::fake([
            '*/api/integrations/users*' => Http::response([
                'data' => [
                    [
                        'id' => $coworker->external_id,
                        'name' => $coworker->name,
                        'apellido_paterno' => $coworker->last_name,
                        'email' => $coworker->email,
                        'cargo' => 'Analista',
                        'departamento' => $department->name,
                        'rol' => 'Colaborador',
                        'permisos' => 'nube_inicio_ver',
                    ],
                    [
                        'id' => $inactive->external_id,
                        'name' => $inactive->name,
                        'email' => $inactive->email,
                        'departamento' => $department->name,
                        'activo' => false,
                    ],
                    [
                        'id' => $outsider->external_id,
                        'name' => $outsider->name,
                        'email' => $outsider->email,
                        'departamento' => 'Otro departamento',
                    ],
                ],
            ]),
        ]);

        $this->authenticated($owner, [
            'nube_mis_archivos_ver',
            'nube_departamento_ver',
            'nube_mis_archivos_crear_carpeta',
            'nube_departamento_crear_carpeta',
            'nube_publicos_crear_carpeta',
            'nube_mis_archivos_subir',
            'nube_departamento_subir',
            'nube_publicos_subir',
        ])->get(route('folders.department'))
            ->assertOk()
            ->assertSee('Todo mi departamento')
            ->assertSee('Personas específicas')
            ->assertSee('Buscar por nombre, correo, cargo o rol')
            ->assertSee('data-collaborator-picker', false)
            ->assertSee('id="upload-collaborators-list"', false)
            ->assertSee('id="folder-collaborators-list"', false)
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-multiselectable="true"', false)
            ->assertSee($coworker->email)
            ->assertSee('Analista')
            ->assertSee('Colaborador')
            ->assertDontSee($inactive->email)
            ->assertDontSee($outsider->email);
    }

    public function test_creation_forms_fix_visibility_and_destinations_to_the_current_section(): void
    {
        $owner = User::factory()->create();
        $privateFolder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Destino privado',
            'visibility' => FileVisibility::Private,
        ]);
        $collaborativeFolder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Destino colaborativo',
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Department,
        ]);
        $publicFolder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Destino público',
            'visibility' => FileVisibility::Public,
        ]);

        Http::fake([
            '*/api/integrations/users*' => Http::response(['data' => []]),
        ]);

        $cases = [
            [
                'route' => route('folders.mine'),
                'visibility' => FileVisibility::Private,
                'folder' => $privateFolder,
                'permissions' => [
                    'nube_mis_archivos_ver',
                    'nube_mis_archivos_subir',
                    'nube_mis_archivos_crear_carpeta',
                ],
            ],
            [
                'route' => route('folders.department'),
                'visibility' => FileVisibility::Collaborative,
                'folder' => $collaborativeFolder,
                'permissions' => [
                    'nube_departamento_ver',
                    'nube_departamento_subir',
                    'nube_departamento_crear_carpeta',
                ],
            ],
            [
                'route' => route('folders.public'),
                'visibility' => FileVisibility::Public,
                'folder' => $publicFolder,
                'permissions' => [
                    'nube_publicos_ver',
                    'nube_publicos_subir',
                    'nube_publicos_crear_carpeta',
                ],
            ],
        ];

        foreach ($cases as $case) {
            $response = $this->authenticated($owner, $case['permissions'])
                ->get($case['route'])
                ->assertOk()
                ->assertSee(
                    'name="visibility" data-sharing-visibility value="'
                        .$case['visibility']->value.'"',
                    false,
                )
                ->assertDontSee(
                    '<select name="visibility" data-sharing-visibility',
                    false,
                )
                ->assertSee(
                    'value="'.$case['folder']->id.'"',
                    false,
                )
                ->assertSee('Determinada por la sección actual.');

            $this->assertSame(
                2,
                substr_count(
                    $response->getContent(),
                    'name="visibility" data-sharing-visibility value="'
                        .$case['visibility']->value.'"',
                ),
            );
        }
    }

    public function test_collaboration_form_reports_when_department_users_cannot_be_loaded(): void
    {
        $owner = User::factory()->create();

        Http::fake([
            '*/api/integrations/users*' => Http::response([], 500),
        ]);

        $this->authenticated($owner, [
            'nube_departamento_ver',
            'nube_departamento_crear_carpeta',
        ])->get(route('folders.department'))
            ->assertOk()
            ->assertSee('No fue posible cargar las personas del departamento.')
            ->assertSee('Volver a intentar');
    }

    public function test_folder_can_be_reclassified_without_changing_its_contents(): void
    {
        $owner = User::factory()->create();
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Declaración 2026',
            'visibility' => FileVisibility::Private,
        ]);
        $child = Folder::factory()->create([
            'parent_id' => $folder->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'visibility' => FileVisibility::Collaborative,
            'collaboration_scope' => CollaborationScope::Department,
        ]);
        $privateFile = $this->file(
            $owner,
            $folder,
            'Privado.pdf',
            FileVisibility::Private,
        );

        $this->authenticated($owner, ['nube_mis_archivos_publicar'])
            ->patch(route('folders.visibility', $folder), [
                'visibility' => FileVisibility::Public->value,
                'folder_context' => $folder->id,
            ])
            ->assertRedirect(route('folders.public'))
            ->assertSessionHas('status');

        $this->assertSame(FileVisibility::Public, $folder->fresh()->visibility);
        $this->assertSame(FileVisibility::Collaborative, $child->fresh()->visibility);
        $this->assertSame(FileVisibility::Private, $privateFile->fresh()->visibility);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'folder.visibility_changed',
            'resource_id' => $folder->id,
        ]);
    }

    public function test_folder_can_be_reclassified_for_selected_collaborators(): void
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $collaborator = User::factory()->create(['department_id' => $department->id]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $department->id,
            'visibility' => FileVisibility::Private,
        ]);

        $this->authenticated($owner, ['nube_mis_archivos_publicar'])
            ->patch(route('folders.visibility', $folder), [
                'visibility' => FileVisibility::Collaborative->value,
                'collaboration_scope' => CollaborationScope::Selected->value,
                'collaborators' => [$collaborator->id],
                'folder_context' => $folder->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $folder->refresh();
        $this->assertSame(FileVisibility::Collaborative, $folder->visibility);
        $this->assertSame(CollaborationScope::Selected, $folder->collaboration_scope);
        $this->assertDatabaseHas('folder_collaborators', [
            'folder_id' => $folder->id,
            'user_id' => $collaborator->id,
        ]);
    }

    public function test_folder_reclassification_requires_publish_permission_and_is_rendered(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create([
            'department_id' => $owner->department_id,
            'name' => 'Persona',
            'last_name' => 'Colaboradora',
        ]);
        $folder = Folder::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'name' => 'Reclasificable',
            'visibility' => FileVisibility::Private,
        ]);
        $file = File::factory()->create([
            'folder_id' => null,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => 'Reclasificable.pdf',
            'original_name' => 'Reclasificable.pdf',
            'visibility' => FileVisibility::Private,
        ]);

        Http::fake([
            '*/api/integrations/users*' => Http::response([
                'data' => [[
                    'id' => $collaborator->external_id,
                    'name' => $collaborator->name,
                    'apellido_paterno' => $collaborator->last_name,
                    'email' => $collaborator->email,
                    'departamento' => $owner->department->name,
                    'cargo' => 'Analista',
                    'rol' => 'Colaborador',
                ]],
            ]),
        ]);

        $this->authenticated($owner, ['nube_mis_archivos_ver'])
            ->patch(route('folders.visibility', $folder), [
                'visibility' => FileVisibility::Public->value,
                'folder_context' => $folder->id,
            ])
            ->assertForbidden();

        $this->authenticated($owner, [
            'nube_mis_archivos_ver',
            'nube_mis_archivos_publicar',
        ])->get(route('folders.mine'))
            ->assertOk()
            ->assertSee('Reclasificar carpeta')
            ->assertSee('Privado (actual)')
            ->assertSee('Colaborativo')
            ->assertSee('Público')
            ->assertSee('value="selected" selected', false)
            ->assertSee('id="folder-visibility-collaborators-'.$folder->id.'-list"', false)
            ->assertSee('id="file-visibility-collaborators-'.$file->id.'-list"', false)
            ->assertSee($collaborator->email)
            ->assertSee(route('folders.visibility', $folder));
    }

    private function file(
        User $owner,
        Folder $folder,
        string $name,
        FileVisibility $visibility,
        ?CollaborationScope $scope = null,
    ): File {
        return File::factory()->create([
            'folder_id' => $folder->id,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => $name,
            'original_name' => $name,
            'visibility' => $visibility,
            'collaboration_scope' => $scope,
        ]);
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
