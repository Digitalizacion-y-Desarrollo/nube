<?php

namespace App\Http\Requests;

use App\Models\Folder;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RenameFolderRequest extends FormRequest
{
    protected $errorBag = 'renameFolder';

    public function authorize(): bool
    {
        $folder = $this->route('folder');

        return $folder instanceof Folder
            && ($this->user()?->can('update', $folder) ?? false);
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
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    private function nameExists(string $name): bool
    {
        /** @var Folder $folder */
        $folder = $this->route('folder');

        return Folder::query()
            ->where('owner_id', $this->user()->id)
            ->where('parent_id', $folder->parent_id)
            ->whereKeyNot($folder->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->exists();
    }
}
