<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'external_id' => fake()->unique()->uuid(),
            'name' => $name,
            'display_name' => str($name)->replace('-', ' ')->title()->toString(),
        ];
    }
}
