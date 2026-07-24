<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'external_id' => fake()->unique()->uuid(),
            'parent_id' => null,
            'parent_external_id' => null,
            'name' => ucfirst($name),
            'abbreviation' => strtoupper(fake()->unique()->lexify('???')),
            'active' => true,
            'last_synced_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
