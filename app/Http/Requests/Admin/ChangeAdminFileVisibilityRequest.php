<?php

namespace App\Http\Requests\Admin;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
use App\Enums\FileVisibility;
use App\Models\File;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAdminFileVisibilityRequest extends FormRequest
{
    protected $errorBag = 'adminFileVisibility';

    public function authorize(): bool
    {
        return $this->user()?->hasRole('superuser')
            && $this->user()?->hasPermission('nube_administracion_administrar');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var File|null $file */
        $file = $this->route('file');
        $selectedScope = $this->input('collaboration_scope')
            === CollaborationScope::Selected->value;

        return [
            'visibility' => [
                'required',
                Rule::enum(FileVisibility::class),
                function (string $attribute, mixed $value, Closure $fail) use ($file): void {
                    if ($file instanceof File
                        && $value === $file->visibility->value
                        && $value !== FileVisibility::Collaborative->value) {
                        $fail('Selecciona una clasificación diferente.');
                    }
                },
            ],
            'file_context' => [
                'required',
                'uuid',
                Rule::in([$file?->id]),
            ],
            'collaboration_scope' => [
                Rule::requiredIf($this->isCollaborative()),
                'nullable',
                Rule::enum(CollaborationScope::class),
            ],
            'collaborators' => [
                Rule::requiredIf($this->isCollaborative() && $selectedScope),
                'nullable',
                'array',
                'min:1',
            ],
            'collaborators.*' => [
                'integer',
                'distinct',
                Rule::notIn([$file?->owner_id]),
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('department_id', $file?->department_id)
                        ->where('active', true),
                ),
            ],
            'collaborator_permissions' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $selectedIds = collect((array) $this->input('collaborators', []))
                        ->map(fn (mixed $id): string => (string) $id);

                    foreach ($value as $userId => $permissions) {
                        if (! $selectedIds->contains((string) $userId)) {
                            $fail('No puedes asignar permisos a una persona que no fue seleccionada.');

                            return;
                        }

                        if (is_array($permissions)
                            && ! in_array(CollaboratorPermission::View->value, $permissions, true)) {
                            $fail('Toda persona seleccionada debe conservar el permiso para ver.');

                            return;
                        }
                    }
                },
            ],
            'collaborator_permissions.*' => ['array', 'min:1'],
            'collaborator_permissions.*.*' => [
                'string',
                Rule::enum(CollaboratorPermission::class),
            ],
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
            'collaboration_scope.required' => 'Selecciona quiénes podrán acceder al archivo colaborativo.',
            'collaboration_scope.enum' => 'El alcance colaborativo no es válido.',
            'collaborators.required' => 'Selecciona al menos una persona.',
            'collaborators.min' => 'Selecciona al menos una persona.',
            'collaborators.*.distinct' => 'No repitas personas en la selección.',
            'collaborators.*.not_in' => 'El propietario ya tiene acceso y no debe seleccionarse.',
            'collaborators.*.exists' => 'Solo puedes seleccionar personas activas del departamento propietario.',
            'collaborator_permissions.*.*.enum' => 'Uno de los permisos internos seleccionados no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->isCollaborative() && ! $this->filled('collaboration_scope')) {
            $this->merge([
                'collaboration_scope' => CollaborationScope::Department->value,
            ]);
        }
    }

    private function isCollaborative(): bool
    {
        return $this->input('visibility') === FileVisibility::Collaborative->value;
    }
}
