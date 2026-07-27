<?php

namespace Database\Factories;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'owner_id' => User::factory(),
            'department_id' => Department::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'visibility' => fake()->randomElement(FileVisibility::cases()),
            'collaboration_scope' => null,
            'path_cache' => null,
        ];
    }
}
