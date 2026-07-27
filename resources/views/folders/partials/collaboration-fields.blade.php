@php
    $contextField = $contextField ?? 'file_context';
    $selectedCollaborators = ($contextId === null || old($contextField) === $contextId)
        ? (array) old('collaborators', [])
        : [];
@endphp

<div data-collaboration-options class="hidden space-y-4 rounded-xl border border-line bg-surface-alt p-4">
    <label class="block">
        <span class="mb-1.5 block text-[13px] font-semibold text-muted">Compartir con</span>
        <select name="collaboration_scope" data-collaboration-scope class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            <option value="department" @selected(old('collaboration_scope', 'department') === 'department')>
                Todo mi departamento
            </option>
            <option value="selected" @selected(old('collaboration_scope') === 'selected')>
                Personas específicas
            </option>
        </select>
    </label>

    <fieldset data-selected-collaborators class="hidden space-y-2">
        <legend class="text-[13px] font-semibold text-muted">
            Personas activas de tu departamento
        </legend>

        @if ($departmentUsersError)
            <div class="rounded-lg border border-danger/25 bg-danger/5 px-3 py-2.5" role="alert">
                <p class="text-xs leading-5 text-danger">{{ $departmentUsersError }}</p>
                <a href="{{ request()->fullUrl() }}" class="mt-1 inline-flex text-xs font-semibold text-brand underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Volver a intentar
                </a>
            </div>
        @else
        @forelse ($departmentUsers as $departmentUser)
            <label class="flex items-start gap-3 rounded-lg border border-line bg-surface px-3 py-2.5">
                <input
                    type="checkbox"
                    name="collaborators[]"
                    value="{{ $departmentUser['id'] }}"
                    @checked(in_array((string) $departmentUser['id'], array_map('strval', $selectedCollaborators), true))
                    class="mt-0.5 size-4 rounded border-line text-brand focus:ring-brand"
                >
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">
                        {{ trim("{$departmentUser['name']} {$departmentUser['last_name']}") }}
                    </span>
                    <span class="block truncate text-xs text-muted">{{ $departmentUser['email'] }}</span>
                    @if ($departmentUser['position'] || $departmentUser['role'])
                        <span class="block truncate text-xs text-muted">
                            {{ collect([$departmentUser['position'], $departmentUser['role']])->filter()->join(' · ') }}
                        </span>
                    @endif
                </span>
            </label>
        @empty
            <p class="text-xs leading-5 text-muted">
                No hay otras personas activas en tu departamento.
            </p>
        @endforelse
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
</div>
