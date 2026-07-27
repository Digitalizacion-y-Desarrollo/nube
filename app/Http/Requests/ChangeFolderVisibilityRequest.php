<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Http\Requests\Concerns\ValidatesCollaborators;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeFolderVisibilityRequest extends FormRequest
{
    use ValidatesCollaborators;

    protected $errorBag = 'changeFolderVisibility';

    public function authorize(): bool
    {
        $folder = $this->route('folder');
        $visibility = is_string($this->input('visibility'))
            ? FileVisibility::tryFrom($this->input('visibility'))
            : null;

        if (! $folder instanceof Folder || $visibility === null) {
            return true;
        }

        return $this->user()?->can('changeVisibility', [$folder, $visibility]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visibility' => [
                'required',
                Rule::enum(FileVisibility::class),
                Rule::notIn([$this->route('folder')?->visibility?->value]),
            ],
            ...$this->collaborationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'visibility.required' => 'Selecciona una clasificación.',
            'visibility.enum' => 'La clasificación seleccionada no es válida.',
            'visibility.not_in' => 'Selecciona una clasificación diferente.',
            ...$this->collaborationMessages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCollaborationForValidation();
    }
}
