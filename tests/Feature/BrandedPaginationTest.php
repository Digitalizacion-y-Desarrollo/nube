<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que `resources/views/vendor/pagination/tailwind.blade.php`
 * reemplaza de verdad el tema por defecto de Laravel (grises genéricos que
 * no usan la paleta del proyecto), no sólo que el archivo exista.
 */
class BrandedPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_paginated_list_uses_the_project_palette_instead_of_laravels_default_grays(): void
    {
        $superuser = $this->superuser();
        Department::factory()->count(3)->create();
        User::factory()->count(15)->create();

        $response = $this->authenticated($superuser)
            ->get(route('admin.users', ['per_page' => 10]))
            ->assertOk();

        $response->assertSee('bg-brand', false);
        $response->assertDontSee('bg-gray-700', false);
        $response->assertDontSee('border-gray-300', false);
        $response->assertDontSee('ring-gray-300', false);
    }

    private function superuser(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superuser', 'display_name' => 'Superusuario']);
        $user->roles()->attach($role, ['created_at' => now()]);
        $user->unsetRelation('roles');

        return $user;
    }

    private function authenticated(User $user): static
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'nube_inicio_ver'],
            ['display_name' => 'nube_inicio_ver'],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id => ['created_at' => now()]]);
        $user->unsetRelation('permissions');

        return $this->actingAs($user)->withSession([
            'access.token' => 'test-token',
            'access.permissions' => ['nube_inicio_ver'],
            'access.roles' => ['superuser'],
            'access.validated_at' => now()->timestamp,
        ]);
    }
}
