<x-layouts.admin title="Archivos" :user="$user">
    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('admin_file_error'))
        <x-ui.alert class="mb-5">{{ session('admin_file_error') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Explorador global de archivos</h2>
            <p class="mt-1 text-sm text-muted">Inventario de archivos con consulta y administración segura, sin exponer rutas físicas ni enlaces públicos.</p>
        </div>
        <span class="text-sm font-semibold text-muted">{{ $files->total() }} {{ $files->total() === 1 ? 'resultado' : 'resultados' }}</span>
    </div>

    <x-ui.collapsible-filters label="Buscar y filtrar">
    <form action="{{ route('admin.files') }}" method="GET" aria-label="Filtros del explorador global">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Nombre</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por nombre visible u original" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Departamento</span>
                <select name="department_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Usuario</span>
                <select name="user_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $owner->id)>{{ trim("{$owner->name} {$owner->last_name}") }} — {{ $owner->email }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Clasificación</span>
                <select name="visibility" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todas</option>
                    @foreach (\App\Enums\FileVisibility::cases() as $visibility)
                        <option value="{{ $visibility->value }}" @selected(($filters['visibility'] ?? '') === $visibility->value)>{{ $visibility->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Tipo</span>
                <select name="type" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm uppercase outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($fileTypes as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Estado</span>
                <select name="status" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Todos</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activos</option>
                    <option value="trashed" @selected(($filters['status'] ?? '') === 'trashed')>En papelera</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Resultados por página</span>
                <select name="per_page" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    @foreach ([10, 20, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Cargado desde</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Cargado hasta</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>
        </div>

        @if ($errors->any())
            <x-ui.alert class="mt-3">Revisa los filtros indicados e intenta nuevamente.</x-ui.alert>
        @endif

        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.files') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-muted hover:bg-soft">Limpiar</a>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-brand/90 focus:outline-none focus:ring-3 focus:ring-brand/20">Aplicar filtros</button>
        </div>
    </form>
    </x-ui.collapsible-filters>

    @unless ($canOperate)
        <x-ui.alert tone="warning" class="mb-5">
            Puedes consultar metadatos. Para descargar, reclasificar o enviar archivos a la papelera también necesitas el permiso <span class="font-semibold">nube_administracion_administrar</span>.
        </x-ui.alert>
    @endunless

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Archivo</th>
                        <th class="px-5 py-3 font-semibold">Propietario</th>
                        <th class="px-5 py-3 font-semibold">Departamento</th>
                        <th class="px-5 py-3 font-semibold">Clasificación</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Tamaño</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($files as $file)
                        <tr class="text-sm hover:bg-warm">
                            <td class="max-w-[300px] px-5 py-3.5">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-soft">
                                        <x-ui.icon name="file-text" :size="18" alt="" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">{{ $file->display_name }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $file->folder?->name ?? 'Raíz' }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-medium">{{ trim("{$file->owner?->name} {$file->owner?->last_name}") ?: 'Sin propietario' }}</span>
                                <span class="block max-w-[220px] truncate text-xs text-muted">{{ $file->owner?->email }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $file->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' => $file->visibility->value === 'private',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $file->visibility->value === 'collaborative',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $file->visibility->value === 'public',
                                ])>{{ $file->visibility->label() }}</span>
                            </td>
                            <td class="px-5 py-3.5 uppercase text-muted">{{ $file->extension ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-muted">{{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 1) }}</td>
                            <td class="px-5 py-3.5">
                                @if ($file->trashed())
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">En papelera</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Activo</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('admin.files.show', $file->id) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Metadatos</a>
                                    @if ($canOperate && ! $file->trashed())
                                        @if ($file->previewType())
                                            <button
                                                type="button"
                                                data-preview-open
                                                data-preview-url="{{ route('admin.files.preview', $file) }}"
                                                data-preview-type="{{ $file->previewType() }}"
                                                data-preview-name="{{ $file->display_name }}"
                                                class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white"
                                            >
                                                Ver
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.files.download', $file) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Descargar</a>
                                        <button type="button" data-modal-open="admin-visibility-{{ $file->id }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Reclasificar</button>
                                        <button type="button" data-modal-open="admin-delete-{{ $file->id }}" class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-500/10">Papelera</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No encontramos archivos</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros o limpia la búsqueda.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($files->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $files->onEachSide(1)->links() }}</div>
        @endif
    </section>

    @if ($canOperate)
        @foreach ($files as $file)
            @unless ($file->trashed())
                <x-ui.modal
                    :id="'admin-visibility-'.$file->id"
                    title="Reclasificar o configurar acceso"
                    :data-modal-auto-open="$errors->adminFileVisibility->any() && old('file_context') === $file->id ? 'true' : null"
                >
                    @php
                        $departmentUsers = $departmentUsersByDepartment
                            ->get($file->department_id, collect())
                            ->reject(fn (array $departmentUser): bool => $departmentUser['id'] === $file->owner_id)
                            ->values()
                            ->all();
                        $defaultCollaborators = $file->collaborators->pluck('id')->all();
                        $defaultCollaboratorPermissions = $file->collaborators->mapWithKeys(function ($collaborator): array {
                            $permissions = collect(\App\Enums\CollaboratorPermission::cases())
                                ->filter(fn ($permission): bool => (bool) $collaborator->pivot->{$permission->pivotColumn()})
                                ->map(fn ($permission): string => $permission->value)
                                ->values()
                                ->all();

                            return [$collaborator->id => $permissions];
                        })->all();
                    @endphp
                    <p class="mb-4 text-sm leading-6 text-muted">Selecciona la clasificación de <span class="font-semibold text-ink">«{{ $file->display_name }}»</span>. Si eliges colaborativo, define si podrá acceder todo {{ $file->department?->name ?? 'el departamento propietario' }} o solo algunas personas.</p>
                    <form action="{{ route('admin.files.visibility', $file) }}" method="POST" class="space-y-4" data-sharing-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="file_context" value="{{ $file->id }}">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-muted">Nueva clasificación</span>
                            <select name="visibility" data-sharing-visibility required class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option value="" @selected($file->visibility !== \App\Enums\FileVisibility::Collaborative) disabled>Selecciona una opción</option>
                                @foreach (\App\Enums\FileVisibility::cases() as $visibility)
                                    @if ($visibility !== $file->visibility || $visibility === \App\Enums\FileVisibility::Collaborative)
                                        <option value="{{ $visibility->value }}" @selected(
                                            old('file_context') === $file->id
                                                ? old('visibility') === $visibility->value
                                                : $file->visibility === \App\Enums\FileVisibility::Collaborative
                                                    && $visibility === \App\Enums\FileVisibility::Collaborative
                                        )>{{ $visibility->label() }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>

                        @include('folders.partials.collaboration-fields', [
                            'contextId' => $file->id,
                            'contextField' => 'file_context',
                            'pickerId' => 'admin-collaborators-'.$file->id,
                            'departmentUsers' => $departmentUsers,
                            'departmentUsersError' => null,
                            'defaultCollaborators' => $defaultCollaborators,
                            'defaultCollaboratorPermissions' => $defaultCollaboratorPermissions,
                            'defaultCollaborationScope' => $file->collaboration_scope?->value ?? 'department',
                            'departmentAudienceLabel' => 'Todo el departamento '.($file->department?->name ?? 'propietario'),
                            'departmentPeopleLabel' => 'Personas activas de '.($file->department?->name ?? 'el departamento propietario'),
                            'errorBag' => $errors->adminFileVisibility,
                        ])
                        @if (old('file_context') === $file->id && $errors->adminFileVisibility->has('visibility'))
                            <x-ui.alert>{{ $errors->adminFileVisibility->first('visibility') }}</x-ui.alert>
                        @endif
                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="'admin-visibility-'.$file->id">Cancelar</x-ui.button>
                            <x-ui.button type="submit">Confirmar cambio</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>

                <x-ui.modal :id="'admin-delete-'.$file->id" title="Enviar archivo a la papelera">
                    <p class="text-sm leading-6 text-muted">El archivo <span class="font-semibold text-ink">«{{ $file->display_name }}»</span> se moverá a la papelera global. Esta acción quedará registrada en auditoría.</p>
                    <form action="{{ route('admin.files.destroy', $file) }}" method="POST" class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="secondary" :data-modal-close="'admin-delete-'.$file->id">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Enviar a papelera</x-ui.button>
                    </form>
                </x-ui.modal>
            @endunless
        @endforeach
    @endif

    <x-files.preview-modal />
</x-layouts.admin>
