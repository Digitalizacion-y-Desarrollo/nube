<?php

namespace App\Http\Requests\Concerns;

use App\Enums\CollaborationScope;
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
