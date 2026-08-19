<?php

namespace Tests\Feature;

use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_security_headers_and_a_nonce_for_inline_scripts(): void
    {
        $response = $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');

        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[^']+'/u", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);

        preg_match("/script-src 'self' 'nonce-([^']+)'/u", $policy, $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringContainsString(
            'nonce="'.htmlspecialchars($matches[1], ENT_QUOTES).'"',
            $response->getContent(),
        );
    }

    public function test_preview_route_allows_same_origin_framing_without_weakening_other_routes(): void
    {
        Storage::fake('nube');
        $user = User::factory()->create();
        $file = File::factory()->create([
            'owner_id' => $user->id,
            'department_id' => $user->department_id,
            'visibility' => FileVisibility::Private,
            'disk' => 'nube',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $previewResponse = $this->authenticated($user, ['nube.archivos.descargar'])
            ->get(route('files.preview', $file))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->assertStringContainsString(
            "frame-ancestors 'self'",
            (string) $previewResponse->headers->get('Content-Security-Policy'),
        );

        $downloadResponse = $this->get(route('files.download', $file))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY');

        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            (string) $downloadResponse->headers->get('Content-Security-Policy'),
        );
    }

    public function test_private_storage_has_no_public_link_and_is_not_served_from_public(): void
    {
        $this->assertSame([], config('filesystems.links'));
        $this->assertFalse(is_link(public_path('storage')));

        $storageRoot = realpath(storage_path('app/nube'));
        $publicRoot = realpath(public_path());

        $this->assertNotFalse($storageRoot);
        $this->assertNotFalse($publicRoot);
        $this->assertFalse(str_starts_with($storageRoot, $publicRoot.DIRECTORY_SEPARATOR));

        $this->get('/storage/archivo-privado.pdf')->assertNotFound();
    }

    public function test_unvalidated_fields_cannot_overwrite_protected_file_metadata(): void
    {
        Storage::fake('nube');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::factory()->create([
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'display_name' => 'original.pdf',
            'original_name' => 'original.pdf',
            'visibility' => FileVisibility::Private,
            'disk' => 'nube',
        ]);
        Storage::disk('nube')->put($file->path, 'contenido');

        $this->authenticated($owner, ['nube_mis_archivos_renombrar'])
            ->patch(route('files.update', $file), [
                'display_name' => 'renombrado.pdf',
                'owner_id' => $other->id,
                'department_id' => $other->department_id,
                'disk' => 'public',
                'path' => '../../public/filtrado.pdf',
                'visibility' => FileVisibility::Public->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $file->refresh();

        $this->assertSame('renombrado.pdf', $file->display_name);
        $this->assertSame($owner->id, $file->owner_id);
        $this->assertSame($owner->department_id, $file->department_id);
        $this->assertSame('nube', $file->disk);
        $this->assertSame(FileVisibility::Private, $file->visibility);
        $this->assertStringNotContainsString('public', $file->path);
    }

    public function test_invalid_or_traversal_identifiers_do_not_resolve_files(): void
    {
        $user = User::factory()->create();

        $this->authenticated($user, ['nube_mis_archivos_descargar'])
            ->get('/mis-archivos/archivos/no-es-un-uuid/descargar')
            ->assertNotFound();

        $this->authenticated($user, ['nube_mis_archivos_descargar'])
            ->get('/mis-archivos/archivos/%2E%2E%2F%2E%2E%2F.env/descargar')
            ->assertNotFound();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticated(User $user, array $permissions): static
    {
        $permissions = array_values(array_unique(['nube_inicio_ver', ...$permissions]));

        foreach ($permissions as $permissionName) {
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
            'access.permissions' => $permissions,
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
