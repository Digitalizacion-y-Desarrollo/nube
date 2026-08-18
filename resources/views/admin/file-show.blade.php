<x-layouts.admin title="Metadatos del archivo" :user="$user">
    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('admin_file_error'))
        <x-ui.alert class="mb-5">{{ session('admin_file_error') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.files') }}" class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline dark:text-gold">← Volver al explorador</a>
            <h2 class="truncate text-xl font-extrabold">{{ $file->display_name }}</h2>
            <p class="mt-1 text-sm text-muted">Metadatos operativos. La ubicación física, el nombre almacenado y el checksum permanecen ocultos.</p>
        </div>

        <div class="flex shrink-0 flex-col items-start gap-2.5 sm:items-end">
            @if ($file->trashed())
                <span class="inline-flex rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">En papelera</span>
            @else
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Activo</span>
            @endif

            @if ($canOperate && ! $file->trashed())
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.files.download', $file) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Descargar</a>
                    <button type="button" data-modal-open="admin-visibility-{{ $file->id }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Reclasificar</button>
                    <button type="button" data-modal-open="admin-delete-{{ $file->id }}" class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-500/10">Enviar a papelera</button>
                </div>
            @elseif (! $canOperate)
                <p class="max-w-[220px] text-right text-[11px] leading-4 text-muted">
                    Consulta de solo lectura. Descargar, reclasificar o enviar a papelera exige el permiso <span class="font-semibold">nube_administracion_administrar</span>.
                </p>
            @endif
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-line bg-surface p-5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Identificación</h3>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><dt class="text-xs font-semibold text-muted">Identificador</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->id }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Nombre visible</dt><dd class="mt-1 break-words text-sm font-medium">{{ $file->display_name }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Nombre original</dt><dd class="mt-1 break-words text-sm font-medium">{{ $file->original_name }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Extensión</dt><dd class="mt-1 text-sm font-medium uppercase">{{ $file->extension ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">MIME</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->mime_type ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Tamaño</dt><dd class="mt-1 text-sm font-medium">{{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 2) }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Clasificación</dt><dd class="mt-1 text-sm font-medium">{{ $file->visibility->label() }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-line bg-surface p-5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Propiedad y ubicación lógica</h3>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold text-muted">Propietario</dt><dd class="mt-1 text-sm font-medium">{{ trim("{$file->owner?->name} {$file->owner?->last_name}") ?: 'Sin propietario' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Correo</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->owner?->email ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Departamento</dt><dd class="mt-1 text-sm font-medium">{{ $file->department?->name ?? 'Sin departamento' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Carpeta</dt><dd class="mt-1 text-sm font-medium">{{ $file->folder?->name ?? 'Raíz' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Alcance colaborativo</dt><dd class="mt-1 text-sm font-medium">{{ $file->collaboration_scope?->value ?? 'No aplica' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Colaboradores seleccionados</dt><dd class="mt-1 text-sm font-medium">{{ $file->collaborators->count() }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-line bg-surface p-5 lg:col-span-2">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Fechas</h3>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs font-semibold text-muted">Carga</dt><dd class="mt-1 text-sm font-medium">{{ $file->uploaded_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Registro</dt><dd class="mt-1 text-sm font-medium">{{ $file->created_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Última actualización</dt><dd class="mt-1 text-sm font-medium">{{ $file->updated_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Eliminación</dt><dd class="mt-1 text-sm font-medium">{{ $file->deleted_at?->format('d/m/Y H:i') ?? 'No aplica' }}</dd></div>
            </dl>
        </div>
    </section>

    @if ($canOperate && ! $file->trashed())
        <x-ui.modal
            :id="'admin-visibility-'.$file->id"
            title="Reclasificar o configurar acceso"
            :data-modal-auto-open="$errors->adminFileVisibility->any() && old('file_context') === $file->id ? 'true' : null"
        >
            @php
                $reclassifyCollaborators = $departmentUsers
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
                    'departmentUsers' => $reclassifyCollaborators,
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
    @endif
</x-layouts.admin>
