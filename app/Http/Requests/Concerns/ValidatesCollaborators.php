<?php

namespace App\Http\Requests\Concerns;

use App\Enums\CollaborationScope;
use App\Enums\CollaboratorPermission;
use App\Enums\FileVisibility;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ValidatesCollaborators
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function collaborationRules(): array
    {
        return [
            'collaboration_scope' => [
                Rule::requiredIf($this->isCollaborative()),
                'nullable',
                Rule::enum(CollaborationScope::class),
            ],
            'collaborators' => [
                Rule::requiredIf(
                    $this->isCollaborative()
                    && $this->input('collaboration_scope') === CollaborationScope::Selected->value,
                ),
                'nullable',
                'array',
                'min:1',
            ],
            'collaborators.*' => [
                'integer',
                'distinct',
                Rule::notIn([$this->user()?->id]),
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('department_id', $this->user()?->department_id)
                        ->where('active', true),
                ),
            ],
            'collaborator_permissions' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
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
            'collaborator_permissions.*' => [
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value) && count($value) !== count(array_unique($value))) {
                        $fail('No repitas permisos para la misma persona.');
                    }
                },
            ],
            'collaborator_permissions.*.*' => [
                'string',
                Rule::enum(CollaboratorPermission::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function collaborationMessages(): array
    {
        return [
            'collaboration_scope.required' => 'Selecciona quiénes podrán acceder al contenido colaborativo.',
            'collaboration_scope.enum' => 'El alcance colaborativo no es válido.',
            'collaborators.required' => 'Selecciona al menos una persona.',
            'collaborators.array' => 'La lista de colaboradores no es válida.',
            'collaborators.min' => 'Selecciona al menos una persona.',
            'collaborators.*.integer' => 'Uno de los colaboradores no es válido.',
            'collaborators.*.distinct' => 'No repitas personas en la selección.',
            'collaborators.*.not_in' => 'El propietario ya tiene acceso y no debe seleccionarse.',
            'collaborators.*.exists' => 'Solo puedes seleccionar personas activas de tu departamento.',
            'collaborator_permissions.array' => 'La configuración de permisos por persona no es válida.',
            'collaborator_permissions.*.array' => 'Los permisos asignados a una persona no son válidos.',
            'collaborator_permissions.*.min' => 'Asigna al menos el permiso para ver.',
            'collaborator_permissions.*.*.enum' => 'Uno de los permisos internos seleccionados no es válido.',
        ];
    }

    protected function prepareCollaborationForValidation(): void
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
