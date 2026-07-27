<?php

namespace Database\Factories;

use App\Enums\FileVisibility;
use App\Models\Department;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'xlsx', 'txt', 'png']);
        $storedName = Str::uuid().".{$extension}";
        $originalName = ucfirst(fake()->words(3, true)).".{$extension}";

        return [
            'folder_id' => null,
            'owner_id' => User::factory(),
            'department_id' => Department::factory(),
            'original_name' => $originalName,
            'display_name' => $originalName,
            'stored_name' => $storedName,
            'disk' => 'nube',
            'path' => "temporales/{$storedName}",
            'extension' => $extension,
            'mime_type' => $this->mimeTypeFor($extension),
            'size_bytes' => fake()->numberBetween(1_024, 10_485_760),
            'visibility' => fake()->randomElement(FileVisibility::cases()),
            'collaboration_scope' => null,
            'checksum' => hash('sha256', $storedName),
            'uploaded_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }

    private function mimeTypeFor(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            default => 'text/plain',
        };
    }
}
