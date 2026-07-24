<?php

namespace Tests\Feature;

use App\Models\Permission;
use Database\Seeders\DemoPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_permissions_are_limited_and_idempotent(): void
    {
        $this->seed(DemoPermissionSeeder::class);
        $this->seed(DemoPermissionSeeder::class);

        $this->assertSame(5, Permission::query()->count());
        $this->assertDatabaseHas('permissions', ['name' => 'nube_inicio_ver']);
        $this->assertDatabaseMissing('permissions', [
            'name' => 'nube_administracion_administrar',
        ]);
    }
}
