<x-layouts.admin title="Auditoría" :user="$user">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Bitácora del sistema</h2>
            <p class="mt-1 text-sm text-muted">Registro inmutable de autenticación, operaciones de usuarios y acciones administrativas.</p>
        </div>
        <span class="text-sm font-semibold text-muted">{{ number_format($logs->total()) }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }}</span>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Eventos registrados</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['total']) }}</strong>
            <span class="text-xs text-muted">{{ number_format($summary['last_day']) }} en las últimas 24 horas</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Acciones administrativas</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['administrative']) }}</strong>
            <span class="text-xs text-muted">Realizadas desde el panel</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Acciones de usuarios</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['user']) }}</strong>
            <span class="text-xs text-muted">Operación normal de la nube</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Actores distintos</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['actors']) }}</strong>
            <span class="text-xs text-muted">Usuarios con eventos registrados</span>
        </article>
    </section>

    <x-ui.collapsible-filters label="Buscar y filtrar">
    <form action="{{ route('admin.audit') }}" method="GET" aria-label="Filtros de auditoría">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Acción o identificador</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar en acción o recurso" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Usuario</span>
                <select name="user_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($people as $person)
                        <option value="{{ $person->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $person->id)>{{ trim("{$person->name} {$person->last_name}") }} — {{ $person->email }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Departamento</span>
                <select name="department_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-muted">Según el área del actor</span>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Acción exacta</span>
                <select name="action" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todas</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Recurso</span>
                <select name="resource_type" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($resourceTypes as $type)
                        <option value="{{ $type }}" @selected(($filters['resource_type'] ?? '') === $type)>{{ class_basename($type) === 'File' ? 'Archivo' : (class_basename($type) === 'Folder' ? 'Carpeta' : class_basename($type)) }}</option>
                    @endforeach
                    <option value="none" @selected(($filters['resource_type'] ?? '') === 'none')>Sin recurso</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Dirección IP</span>
                <input type="text" name="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="Ej. 10.0.0.5" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>
            <label class="block">
                <span class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-muted">
                    Origen
                    <x-ui.help-tip label="Cómo se distingue el origen de un evento">
                        <strong class="block text-ink">Administrativas</strong>
                        Acciones realizadas desde el panel de superusuario.
                        <strong class="mt-2 block text-ink">De usuarios</strong>
                        Operación normal de la nube personal de cada quien.
                    </x-ui.help-tip>
                </span>
                <select name="scope" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="all" @selected(($filters['scope'] ?? 'all') === 'all')>Todos</option>
                    <option value="administrative" @selected(($filters['scope'] ?? '') === 'administrative')>Administrativas</option>
                    <option value="user" @selected(($filters['scope'] ?? '') === 'user')>De usuarios</option>
                </select>
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-muted">Desde</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-muted">Hasta</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                </label>
            </div>
        </div>
        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:items-end sm:justify-between">
            <label class="block sm:w-44">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Resultados por página</span>
                <select name="per_page" class="h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    @foreach ([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <a href="{{ route('admin.audit') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-muted hover:bg-soft">Limpiar</a>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-brand/90">Aplicar filtros</button>
            </div>
        </div>
    </form>
    </x-ui.collapsible-filters>

    <x-ui.alert tone="info" class="mb-5">
        La bitácora es de solo lectura. Los eventos no pueden editarse ni eliminarse desde la plataforma.
    </x-ui.alert>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Evento</th>
                        <th class="px-5 py-3 font-semibold">Origen</th>
                        <th class="px-5 py-3 font-semibold">Actor</th>
                        <th class="px-5 py-3 font-semibold">Departamento</th>
                        <th class="px-5 py-3 font-semibold">Recurso</th>
                        <th class="px-5 py-3 font-semibold">Dirección IP</th>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-5 py-3 text-right font-semibold">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($logs as $log)
                        <tr class="text-sm hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full bg-brand/8 px-2.5 py-1 text-[11px] font-semibold text-brand dark:text-white">{{ $log->action }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $log->administrative,
                                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $log->administrative,
                                ])>{{ $log->administrative ? 'Administrativa' : 'De usuario' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-medium">{{ trim("{$log->user?->name} {$log->user?->last_name}") ?: 'Sistema' }}</span>
                                <span class="block text-xs text-muted">{{ $log->user?->email ?? 'Proceso automático' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $log->user?->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5 text-muted">
                                <span class="block">{{ $log->resource_label }}</span>
                                <span class="block max-w-[220px] truncate text-xs">{{ $log->resource_name ?? ($log->resource_id ?: '—') }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-muted">{{ $log->ip_address ?: 'No disponible' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-muted">
                                <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d/m/Y H:i') }}</time>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.audit.show', $log) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Consultar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No encontramos eventos</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros para ampliar la búsqueda.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $logs->onEachSide(1)->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
