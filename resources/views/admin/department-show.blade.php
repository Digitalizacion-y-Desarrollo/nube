<x-layouts.admin :title="$department->name" :user="$user">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('admin.departments') }}" class="mb-2 inline-flex text-xs font-semibold text-brand hover:underline dark:text-gold">← Volver a departamentos</a>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-extrabold">{{ $department->name }}</h2>
                <span @class([
                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $department->active,
                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $department->active,
                ])>{{ $department->active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <p class="mt-1 text-sm text-muted">{{ $department->abbreviation ?: 'Sin abreviatura' }} · {{ $department->parent?->name ?? 'Área raíz' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.users', ['department_id' => $department->id]) }}" class="inline-flex h-10 items-center rounded-lg border border-line bg-surface px-4 text-sm font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Ver todos los usuarios</a>
            <a href="{{ route('admin.files', ['department_id' => $department->id]) }}" class="inline-flex h-10 items-center rounded-lg bg-brand px-4 text-sm font-semibold text-white hover:bg-brand/90">Ver todos los archivos</a>
        </div>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-line bg-surface p-4"><span class="text-xs font-semibold text-muted">Usuarios</span><strong class="mt-2 block text-2xl">{{ number_format($summary['active_users']) }}</strong><span class="text-xs text-muted">de {{ number_format($summary['users']) }} activos</span></article>
        <article class="rounded-xl border border-line bg-surface p-4"><span class="text-xs font-semibold text-muted">Archivos activos</span><strong class="mt-2 block text-2xl">{{ number_format($summary['files']) }}</strong><span class="text-xs text-muted">{{ number_format($summary['trashed_files']) }} en papelera</span></article>
        <article class="rounded-xl border border-line bg-surface p-4"><span class="text-xs font-semibold text-muted">Carpetas activas</span><strong class="mt-2 block text-2xl">{{ number_format($summary['folders']) }}</strong><span class="text-xs text-muted">{{ number_format($summary['trashed_folders']) }} en papelera</span></article>
        <article class="rounded-xl border border-line bg-surface p-4"><span class="text-xs font-semibold text-muted">Almacenamiento retenido</span><strong class="mt-2 block text-2xl">{{ $summary['total_storage'] }}</strong><span class="text-xs text-muted">{{ $summary['active_storage'] }} activo · {{ $summary['trashed_storage'] }} en papelera</span></article>
    </section>

    <section class="mb-5 grid gap-4 lg:grid-cols-[1fr_1.4fr]">
        <article class="rounded-xl border border-line bg-surface p-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Sincronización e identidad</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div><dt class="text-xs font-semibold text-muted">Identificador externo</dt><dd class="mt-1 break-all text-sm font-medium">{{ $department->external_id }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Última sincronización</dt><dd class="mt-1 text-sm font-medium">{{ $department->last_synced_at?->format('d/m/Y H:i') ?? 'Sin sincronizar' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Área superior</dt><dd class="mt-1 text-sm font-medium">{{ $department->parent?->name ?? 'Área raíz' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Áreas dependientes</dt><dd class="mt-1 text-sm font-medium">{{ number_format($department->children->count()) }}</dd></div>
            </dl>
            <x-ui.alert tone="warning" class="mt-4">Los departamentos se crean y actualizan exclusivamente desde el sistema central de Accesos.</x-ui.alert>
        </article>

        <article class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="border-b border-line px-5 py-4"><h3 class="font-bold">Usuarios relacionados</h3></div>
            <div class="divide-y divide-line">
                @forelse ($departmentUsers as $listedUser)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm">
                        <span class="min-w-0"><strong class="block truncate">{{ trim("{$listedUser->name} {$listedUser->last_name}") }}</strong><span class="block truncate text-xs text-muted">{{ $listedUser->email }}</span></span>
                        <span class="shrink-0 text-xs font-semibold {{ $listedUser->active ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted' }}">{{ $listedUser->active ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-muted">No hay usuarios sincronizados en este departamento.</p>
                @endforelse
            </div>
            @if ($departmentUsers->hasPages())<div class="border-t border-line px-5 py-4">{{ $departmentUsers->onEachSide(1)->links() }}</div>@endif
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-xl border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-5 py-4"><div><h3 class="font-bold">Archivos colaborativos y públicos</h3><p class="mt-0.5 text-xs text-muted">Contenido activo relacionado con el departamento.</p></div></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-sm">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted"><tr><th class="px-5 py-3">Archivo</th><th class="px-5 py-3">Propietario</th><th class="px-5 py-3">Clasificación</th><th class="px-5 py-3">Tamaño</th><th class="px-5 py-3 text-right">Detalle</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($departmentFiles as $file)
                        <tr class="hover:bg-warm"><td class="px-5 py-3.5"><strong class="block max-w-[300px] truncate">{{ $file->display_name }}</strong><span class="text-xs text-muted">{{ $file->folder?->name ?? 'Raíz' }}</span></td><td class="px-5 py-3.5">{{ trim("{$file->owner?->name} {$file->owner?->last_name}") ?: 'Sin propietario' }}</td><td class="px-5 py-3.5">{{ $file->visibility->label() }}</td><td class="px-5 py-3.5">{{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 1) }}</td><td class="px-5 py-3.5 text-right"><a href="{{ route('admin.files.show', $file->id) }}" class="text-xs font-semibold text-brand hover:underline dark:text-gold">Metadatos</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-muted">No hay archivos colaborativos o públicos activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departmentFiles->hasPages())<div class="border-t border-line px-5 py-4">{{ $departmentFiles->onEachSide(1)->links() }}</div>@endif
    </section>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="border-b border-line px-5 py-4"><h3 class="font-bold">Actividad reciente</h3></div>
        <div class="divide-y divide-line">
            @forelse ($recentActivity as $log)
                <div class="flex flex-col gap-1 px-5 py-3.5 text-sm sm:flex-row sm:items-center sm:justify-between"><span><strong class="font-semibold">{{ $log->action }}</strong><span class="ml-2 text-xs text-muted">{{ trim("{$log->user?->name} {$log->user?->last_name}") ?: 'Sistema' }}</span></span><time datetime="{{ $log->created_at?->toIso8601String() }}" class="text-xs text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</time></div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-muted">No hay actividad reciente relacionada con este departamento.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
