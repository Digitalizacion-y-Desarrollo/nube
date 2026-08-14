<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_the_initials_photo_and_account_data(): void
    {
        $department = Department::factory()->create(['name' => 'Obras Públicas']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
        ]);

        $this->authenticated($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertSee('Estás usando tus iniciales')
            ->assertSee('AP')
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('Obras Públicas')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertDontSee(asset('assets/figma/avatar.png'), false);
    }

    public function test_the_upload_form_exposes_the_preview_hooks_and_the_real_limit(): void
    {
        config()->set('nube.avatars.max_size_kb', 10240);
        $user = User::factory()->create();

        $this->authenticated($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-avatar-preview', false)
            ->assertSee('data-avatar-input', false)
            ->assertSee('data-avatar-cancel', false)
            ->assertSee('data-avatar-max-kb="10240"', false)
            ->assertSee('data-avatar-extensions="jpg,jpeg,png"', false)
            ->assertSee('Vista previa')
            ->assertSee('Tamaño máximo: 10 MB');
    }

    public function test_a_photo_larger_than_the_previous_limit_is_now_accepted(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        // 8 MB: se rechazaba con el límite anterior de 2 MB.
        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('grande.jpg')->size(8 * 1024),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->hasAvatar());
    }

    public function test_the_default_photo_is_a_self_contained_svg_with_the_initials(): void
    {
        $user = User::factory()->create(['name' => 'Ana', 'last_name' => 'Pérez']);

        $uri = $user->avatarUrl();
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('>AP<', $svg);
        $this->assertStringContainsString('#601633', $svg);

        // No debe cargar recursos externos. El `xmlns` es una declaración de
        // espacio de nombres obligatoria, no una descarga, así que se descarta
        // antes de comprobar que no queda ninguna referencia remota.
        $withoutNamespace = str_replace('http://www.w3.org/2000/svg', '', $svg);

        $this->assertStringNotContainsString('http', $withoutNamespace);
        $this->assertStringNotContainsString('<image', $withoutNamespace);
        $this->assertStringNotContainsString('href', $withoutNamespace);
        $this->assertStringNotContainsString('url(', $withoutNamespace);
        $this->assertStringNotContainsString('<script', $withoutNamespace);
    }

    #[DataProvider('initialsProvider')]
    public function test_initials_cover_names_without_surname_accents_and_symbols(
        ?string $name,
        ?string $lastName,
        string $expected,
    ): void {
        $user = User::factory()->create(['name' => $name, 'last_name' => $lastName]);

        $this->assertSame($expected, $user->initials());
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: string}>
     */
    public static function initialsProvider(): array
    {
        return [
            'nombre y apellido' => ['Ana', 'Pérez', 'AP'],
            'con acento inicial' => ['Ángela', 'Ñuño', 'ÁÑ'],
            'sin apellido' => ['Ana', null, 'AN'],
            'apellido vacío' => ['Ana', '', 'AN'],
            'nombre de una letra' => ['A', null, 'A'],
            'nombre compuesto' => ['Juan Carlos', 'López Díaz', 'JL'],
            'con símbolos' => ['@ana', '#pérez', 'AP'],
            'sin datos' => ['', null, '—'],
        ];
    }

    public function test_the_navigation_avatar_links_to_the_profile(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('profile.edit'), false)
            ->assertSee('Editar foto de perfil');
    }

    public function test_a_user_can_upload_a_photo_that_is_stored_privately_and_audited(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user)
            ->from(route('profile.edit'))
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('retrato.jpg', 240, 240),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue($user->hasAvatar());
        $this->assertStringStartsWith("perfiles/{$user->id}/", (string) $user->avatar_path);
        Storage::disk('nube')->assertExists((string) $user->avatar_path);

        // El nombre original no se conserva como nombre físico.
        $this->assertStringNotContainsString('retrato', (string) $user->avatar_path);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.avatar_updated',
        ]);
    }

    public function test_the_stored_photo_is_served_through_the_controller_and_never_by_url(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('retrato.png', 200, 200),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->authenticated($user)
            ->get(route('profile.avatar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // La ruta física nunca aparece en la interfaz ni es alcanzable.
        $this->authenticated($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee((string) $user->avatar_path);

        $this->get('/storage/'.$user->avatar_path)->assertNotFound();
    }

    public function test_replacing_the_photo_removes_the_previous_file(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('primera.jpg'),
            ])
            ->assertRedirect();

        $user->refresh();
        $firstPath = (string) $user->avatar_path;

        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('segunda.jpg'),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotSame($firstPath, (string) $user->avatar_path);
        Storage::disk('nube')->assertMissing($firstPath);
        Storage::disk('nube')->assertExists((string) $user->avatar_path);
    }

    public function test_a_user_can_return_to_the_default_photo(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create(['name' => 'Ana', 'last_name' => 'Pérez']);

        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('retrato.jpg'),
            ])
            ->assertRedirect();

        $user->refresh();
        $path = (string) $user->avatar_path;

        $this->authenticated($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertFalse($user->hasAvatar());
        Storage::disk('nube')->assertMissing($path);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $user->avatarUrl());

        $this->authenticated($user)
            ->get(route('profile.avatar'))
            ->assertNotFound();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.avatar_removed',
        ]);
    }

    public function test_invalid_files_are_rejected_without_touching_the_current_photo(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $rejected = [
            UploadedFile::fake()->create('documento.pdf', 40, 'application/pdf'),
            UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
            UploadedFile::fake()->image('enorme.jpg')->size(
                ((int) config('nube.avatars.max_size_kb')) + 512,
            ),
        ];

        foreach ($rejected as $upload) {
            $this->authenticated($user)
                ->from(route('profile.edit'))
                ->post(route('profile.avatar.update'), ['avatar' => $upload])
                ->assertRedirect(route('profile.edit'))
                ->assertSessionHasErrors('avatar');
        }

        $user->refresh();

        $this->assertFalse($user->hasAvatar());
        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.avatar_updated',
        ]);
    }

    public function test_a_user_never_receives_another_persons_photo(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->authenticated($owner)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('retrato.jpg'),
            ])
            ->assertRedirect();

        $owner->refresh();

        // La ruta sirve siempre la foto de quien la solicita, no la de otro.
        $this->authenticated($other)
            ->get(route('profile.avatar'))
            ->assertNotFound();

        $this->assertNull($other->fresh()->avatar_path);
    }

    public function test_the_profile_requires_an_active_session(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('profile.avatar'))->assertRedirect(route('login'));
        $this->post(route('profile.avatar.update'))->assertRedirect(route('login'));
        $this->delete(route('profile.avatar.destroy'))->assertRedirect(route('login'));
    }

    public function test_the_photo_survives_a_synchronization_with_the_access_system(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();

        $this->authenticated($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('retrato.jpg'),
            ])
            ->assertRedirect();

        $user->refresh();
        $path = (string) $user->avatar_path;

        // La sincronización reescribe los campos oficiales, nunca la foto local.
        $user->update([
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.test',
            'last_synced_at' => now(),
        ]);

        $this->assertSame($path, (string) $user->fresh()->avatar_path);
    }

    private function authenticated(User $user): static
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
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
