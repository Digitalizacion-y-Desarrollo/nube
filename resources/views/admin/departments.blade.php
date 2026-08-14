<x-layouts.admin title="Departamentos" :user="$user">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Departamentos sincronizados</h2>
            <p class="mt-1 text-sm text-muted">Supervisión de áreas recibidas desde Accesos; esta sección es exclusivamente de consulta.</p>
        </div>
        <span class="text-sm font-semibold text-muted">{{ $departments->total() }} {{ $departments->total() === 1 ? 'departamento' : 'departamentos' }}</span>
    </div>

    <form action="{{ route('admin.departments') }}" method="GET" class="mb-5 rounded-xl border border-line bg-surface p-4" aria-label="Filtros de departamentos">
        <div class="grid gap-3 sm:grid-cols-3">
            <label class="block sm:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Nombre o abreviatura</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar departamento" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Estado</span>
                <select name="status" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Todos</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activos</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivos</option>
                </select>
            </label>
        </div>
        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.departments') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-muted hover:bg-soft">Limpiar</a>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-brand/90">Aplicar filtros</button>
        </div>
    </form>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Departamento</th>
                        <th class="px-5 py-3 text-center font-semibold">Usuarios</th>
                        <th class="px-5 py-3 text-center font-semibold">Archivos</th>
                        <th class="px-5 py-3 text-center font-semibold">Carpetas</th>
                        <th class="px-5 py-3 font-semibold">Almacenamiento</th>
                        <th class="px-5 py-3 font-semibold">Sincronización</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($departments as $department)
                        <tr class="text-sm hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <span class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand/8">
                                        <x-ui.icon name="building" :size="18" alt="" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-semibold">{{ $department->name }}</span>
                                        <span class="block text-xs text-muted">{{ $department->abbreviation ?: 'Sin abreviatura' }} · {{ $department->parent?->name ?? 'Área raíz' }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="block font-semibold">{{ number_format($department->active_users_count) }}</span>
                                <span class="text-[11px] text-muted">de {{ number_format($department->users_count) }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="block font-semibold">{{ number_format($department->files_count) }}</span>
                                <span class="text-[11px] text-muted">{{ number_format($department->trashed_files_count) }} en papelera</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="block font-semibold">{{ number_format($department->folders_count) }}</span>
                                <span class="text-[11px] text-muted">{{ number_format($department->trashed_folders_count) }} en papelera</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-semibold">{{ $department->active_storage }}</span>
                                <span class="text-[11px] text-muted">{{ $department->trashed_storage }} en papelera</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="block text-xs font-medium">{{ $department->last_synced_at?->format('d/m/Y H:i') ?? 'Sin sincronizar' }}</span>
                                @if ($department->last_synced_at)
                                    <span class="text-[11px] text-muted">{{ $department->last_synced_at->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $department->active,
                                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $department->active,
                                ])>{{ $department->active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.departments.show', $department) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Consultar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No encontramos departamentos</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros o espera la próxima sincronización con Accesos.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $departments->onEachSide(1)->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
