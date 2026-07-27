<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Http\Requests\Concerns\ValidatesCollaborators;
use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeFileVisibilityRequest extends FormRequest
{
    use ValidatesCollaborators;

    protected $errorBag = 'changeVisibility';

    public function authorize(): bool
    {
        $file = $this->route('file');
        $visibility = is_string($this->input('visibility'))
            ? FileVisibility::tryFrom($this->input('visibility'))
            : null;

        if (! $file instanceof File || $visibility === null) {
            return true;
        }

        return $this->user()?->can('changeVisibility', [$file, $visibility]) ?? false;
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
                Rule::notIn([$this->route('file')?->visibility?->value]),
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
