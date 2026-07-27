<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Http\Requests\Concerns\ValidatesCollaborators;
use App\Models\Folder;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
{
    use ValidatesCollaborators;

    protected $errorBag = 'createFolder';

    public function authorize(): bool
    {
        $parentId = $this->input('parent_id');
        $visibility = is_string($this->input('visibility'))
            ? FileVisibility::tryFrom($this->input('visibility'))
            : null;

        if ($visibility === null) {
            return true;
        }

        if (! is_string($parentId) || ! Str::isUuid($parentId)) {
            return $this->user()?->can('create', [Folder::class, null, $visibility]) ?? false;
        }

        $parent = Folder::query()->find($parentId);

        return $parent !== null
            && ($this->user()?->can('create', [Folder::class, $parent, $visibility]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:150',
                'regex:/^[^\/\\\\\x00-\x1F\x7F]+$/u',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $this->nameExists($value)) {
                        $fail('Ya existe una carpeta con ese nombre en esta ubicación.');
                    }
                },
            ],
            'parent_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'visibility' => ['required', Rule::enum(FileVisibility::class)],
            ...$this->collaborationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe un nombre para la carpeta.',
            'name.max' => 'El nombre no puede exceder 150 caracteres.',
            'name.regex' => 'El nombre no puede contener diagonales ni caracteres de control.',
            'parent_id.uuid' => 'La carpeta de destino no es válida.',
            'parent_id.exists' => 'La carpeta de destino no existe o fue eliminada.',
            'visibility.required' => 'Selecciona la clasificación de la carpeta.',
            'visibility.enum' => 'La clasificación seleccionada no es válida.',
            ...$this->collaborationMessages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if ($this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }

        if (! $this->has('visibility')) {
            $this->merge(['visibility' => FileVisibility::Private->value]);
        }

        $this->prepareCollaborationForValidation();
    }

    private function nameExists(string $name): bool
    {
        $query = Folder::query()
            ->where('owner_id', $this->user()->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        $parentId = $this->input('parent_id');

        return $query
            ->when(
                is_string($parentId) && $parentId !== '',
                fn ($folders) => $folders->where('parent_id', $parentId),
                fn ($folders) => $folders->whereNull('parent_id'),
            )
            ->exists();
    }
}
