<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Models\File;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RenameFileRequest extends FormRequest
{
    protected $errorBag = 'renameFile';

    public function authorize(): bool
    {
        $file = $this->route('file');

        return $file instanceof File
            && ($this->user()?->can('update', $file) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => [
                'bail',
                'required',
                'string',
                'max:255',
                'regex:/^[^\/\\\\\x00-\x1F\x7F]+$/u',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || in_array($value, ['.', '..'], true)) {
                        $fail('El nombre del archivo no es válido.');

                        return;
                    }

                    if ($this->nameExists($value)) {
                        $fail('Ya existe un archivo con ese nombre en esta ubicación.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_name.required' => 'Escribe un nombre para el archivo.',
            'display_name.max' => 'El nombre no puede exceder 255 caracteres.',
            'display_name.regex' => 'El nombre no puede contener diagonales ni caracteres de control.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('display_name'))) {
            $this->merge(['display_name' => trim($this->input('display_name'))]);
        }
    }

    private function nameExists(string $name): bool
    {
        /** @var File $file */
        $file = $this->route('file');

        $query = File::query()
            ->where('folder_id', $file->folder_id)
            ->where('visibility', $file->visibility)
            ->whereKeyNot($file->id)
            ->whereRaw('LOWER(display_name) = ?', [Str::lower($name)]);

        $file->visibility === FileVisibility::Private
            ? $query->where('owner_id', $file->owner_id)
            : $query->where('department_id', $file->department_id);

        return $query->exists();
    }
}
