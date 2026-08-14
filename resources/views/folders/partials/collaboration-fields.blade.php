@php
    $contextField = $contextField ?? 'file_context';
    $defaultCollaborators = $defaultCollaborators ?? [];
    $hasOldContext = old($contextField) !== null;
    $usesOldContext = $contextId === null
        || ($hasOldContext && (string) old($contextField) === (string) $contextId);
    $selectedCollaborators = $usesOldContext
        ? (array) old('collaborators', $defaultCollaborators)
        : $defaultCollaborators;
    $defaultCollaboratorPermissions = $defaultCollaboratorPermissions ?? [];
    $selectedCollaboratorPermissions = $usesOldContext
        ? (array) old('collaborator_permissions', $defaultCollaboratorPermissions)
        : $defaultCollaboratorPermissions;
    $pickerId = $pickerId ?? 'collaborators-'.$contextId;
    $defaultCollaborationScope = $defaultCollaborationScope
        ?? ($contextId === null ? 'department' : 'selected');
    $selectedCollaborationScope = $usesOldContext
        ? old('collaboration_scope', $defaultCollaborationScope)
        : $defaultCollaborationScope;
    $departmentAudienceLabel = $departmentAudienceLabel ?? 'Todo mi departamento';
    $departmentPeopleLabel = $departmentPeopleLabel ?? 'Personas activas de tu departamento';
@endphp

<div data-collaboration-options class="hidden space-y-4 rounded-xl border border-line bg-surface-alt p-4">
    <label class="block">
        <span class="mb-1.5 block text-[13px] font-semibold text-muted">Compartir con</span>
        <select name="collaboration_scope" data-collaboration-scope class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            <option value="department" @selected($selectedCollaborationScope === 'department')>
                {{ $departmentAudienceLabel }}
            </option>
            <option value="selected" @selected($selectedCollaborationScope === 'selected')>
                Personas específicas
            </option>
        </select>
    </label>

    <fieldset data-selected-collaborators class="hidden space-y-2">
        <legend class="text-[13px] font-semibold text-muted">
            {{ $departmentPeopleLabel }}
        </legend>
        <input type="hidden" name="collaborators_configured" value="1">

        @if ($departmentUsersError)
            <div class="rounded-lg border border-danger/25 bg-danger/5 px-3 py-2.5" role="alert">
                <p class="text-xs leading-5 text-danger">{{ $departmentUsersError }}</p>
                <a href="{{ request()->fullUrl() }}" class="mt-1 inline-flex text-xs font-semibold text-brand underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Volver a intentar
                </a>
            </div>
        @elseif (count($departmentUsers) > 0)
            <div data-collaborator-picker class="space-y-2">
                <div class="relative">
                    <x-ui.icon name="search" :size="17" alt="" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        id="{{ $pickerId }}-search"
                        type="search"
                        data-collaborator-search
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="{{ $pickerId }}-list"
                        autocomplete="off"
                        placeholder="Buscar por nombre, correo, cargo o rol"
                        class="h-[46px] w-full rounded-lg border border-line bg-surface pl-10 pr-3.5 text-sm text-ink outline-none transition placeholder:text-muted focus:border-brand focus:ring-3 focus:ring-brand/10"
                    >
                </div>

                <p data-collaborator-summary class="text-xs text-muted" aria-live="polite">
                    Ninguna persona seleccionada
                </p>

                <div
                    id="{{ $pickerId }}-list"
                    data-collaborator-list
                    role="listbox"
                    aria-multiselectable="true"
                    aria-label="Personas del departamento"
                    class="hidden max-h-64 space-y-1 overflow-y-auto rounded-xl border border-line bg-surface p-1.5 shadow-lg"
                >
                    @foreach ($departmentUsers as $departmentUser)
                        @php
                            $fullName = trim("{$departmentUser['name']} {$departmentUser['last_name']}");
                            $isSelected = in_array(
                                (string) $departmentUser['id'],
                                array_map('strval', $selectedCollaborators),
                                true,
                            );
                            $searchValue = collect([
                                $fullName,
                                $departmentUser['email'],
                                $departmentUser['position'],
                                $departmentUser['role'],
                            ])->filter()->join(' ');
                            $assignedPermissions = (array) (
                                $selectedCollaboratorPermissions[$departmentUser['id']]
                                ?? $selectedCollaboratorPermissions[(string) $departmentUser['id']]
                                ?? \App\Enums\CollaboratorPermission::defaults()
                            );
                        @endphp
                        <div
                            id="{{ $pickerId }}-option-{{ $loop->index }}"
                            data-collaborator-option
                            data-search-value="{{ $searchValue }}"
                            role="option"
                            aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                            class="collaborator-option rounded-lg border border-transparent px-3 py-2.5 transition hover:border-line hover:bg-soft"
                        >
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="collaborators[]"
                                    value="{{ $departmentUser['id'] }}"
                                    data-collaborator-checkbox
                                    @checked($isSelected)
                                    class="mt-0.5 size-4 rounded border-line text-brand focus:ring-brand"
                                >
                                <span class="min-w-0">
                                    <span data-collaborator-name class="block truncate text-sm font-semibold text-ink">
                                        {{ $fullName }}
                                    </span>
                                    <span class="block truncate text-xs text-muted">{{ $departmentUser['email'] }}</span>
                                    @if ($departmentUser['position'] || $departmentUser['role'])
                                        <span class="block truncate text-xs text-muted">
                                            {{ collect([$departmentUser['position'], $departmentUser['role']])->filter()->join(' · ') }}
                                        </span>
                                    @endif
                                </span>
                            </label>

                            <fieldset
                                data-collaborator-permissions
                                @class([
                                    'ml-7 mt-3 border-t border-line pt-3',
                                    'hidden' => ! $isSelected,
                                ])
                            >
                                <legend class="sr-only">Permisos para {{ $fullName }}</legend>
                                <input
                                    type="hidden"
                                    name="collaborator_permissions[{{ $departmentUser['id'] }}][]"
                                    value="view"
                                    data-collaborator-permission-input
                                    @disabled(! $isSelected)
                                >
                                <div class="flex flex-wrap gap-x-4 gap-y-2">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink">
                                        <span class="flex size-4 items-center justify-center rounded bg-brand text-[10px] text-white" aria-hidden="true">✓</span>
                                        Ver
                                    </span>
                                    @foreach (\App\Enums\CollaboratorPermission::cases() as $permission)
                                        @continue($permission === \App\Enums\CollaboratorPermission::View)
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-ink">
                                            <input
                                                type="checkbox"
                                                name="collaborator_permissions[{{ $departmentUser['id'] }}][]"
                                                value="{{ $permission->value }}"
                                                data-collaborator-permission-input
                                                @checked(in_array($permission->value, $assignedPermissions, true))
                                                @disabled(! $isSelected)
                                                class="size-3.5 rounded border-line text-brand focus:ring-brand"
                                            >
                                            {{ $permission->label() }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                    @endforeach

                    <p data-collaborator-empty class="hidden px-3 py-5 text-center text-xs leading-5 text-muted">
                        No encontramos personas con esa búsqueda.
                    </p>
                </div>

                <p class="text-[11px] leading-5 text-muted">
                    Selecciona cada persona y configura sus permisos internos.
                    En carpetas, la misma configuración se hereda al crear archivos dentro de ellas.
                </p>
            </div>
        @else
            <p class="text-xs leading-5 text-muted">
                No hay otras personas activas en tu departamento.
            </p>
        @endif
    </fieldset>

    @if ($errorBag->has('collaboration_scope'))
        <x-ui.alert>{{ $errorBag->first('collaboration_scope') }}</x-ui.alert>
    @endif
    @if ($errorBag->has('collaborators') || $errorBag->has('collaborators.*'))
        <x-ui.alert>
            {{ $errorBag->first('collaborators') ?: $errorBag->first('collaborators.*') }}
        </x-ui.alert>
    @endif
    @if ($errorBag->has('collaborator_permissions') || $errorBag->has('collaborator_permissions.*'))
        <x-ui.alert>
            {{ $errorBag->first('collaborator_permissions') ?: $errorBag->first('collaborator_permissions.*') }}
        </x-ui.alert>
    @endif
</div>
