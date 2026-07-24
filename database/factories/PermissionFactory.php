<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $resource = fake()->unique()->word();
        $action = fake()->randomElement(['ver', 'crear', 'editar', 'eliminar']);

        return [
            'external_id' => fake()->unique()->uuid(),
            'name' => "nube.{$resource}.{$action}",
            'display_name' => ucfirst("{$action} {$resource}"),
        ];
    }
}
