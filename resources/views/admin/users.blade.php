<x-layouts.admin title="Usuarios" :user="$user">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Usuarios sincronizados</h2>
            <p class="mt-1 text-sm text-muted">Supervisión de identidades, roles, permisos efectivos y consumo; la fuente oficial continúa siendo el sistema de Accesos.</p>
        </div>
        <span class="text-sm font-semibold text-muted">{{ number_format($users->total()) }} {{ $users->total() === 1 ? 'usuario' : 'usuarios' }}</span>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Usuarios sincronizados</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['users']) }}</strong>
            <span class="text-xs text-muted">Identidades recibidas de Accesos</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Activos</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['active_users']) }}</strong>
            <span class="text-xs text-muted">{{ number_format($summary['inactive_users']) }} inactivos</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Almacenamiento retenido</span>
            <strong class="mt-2 block text-2xl">{{ $summary['storage'] }}</strong>
            <span class="text-xs text-muted">Activo y papelera de todos los usuarios</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Espacio activo</span>
            <strong class="mt-2 block text-2xl">{{ $summary['active_storage'] }}</strong>
            <span class="text-xs text-muted">{{ $summary['trashed_storage'] }} en papelera</span>
        </article>
    </section>

    <form action="{{ route('admin.users') }}" method="GET" class="mb-5 rounded-xl border border-line bg-surface p-4" aria-label="Filtros de usuarios">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="block xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Nombre, correo o identificador</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar usuario" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
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
                <span class="mb-1.5 block text-xs font-semibold text-muted">Rol</span>
                <select name="role" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->display_name ?: $role->name }}</option>
                    @endforeach
                </select>
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
        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:items-end sm:justify-between">
            <label class="block sm:w-44">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Resultados por página</span>
                <select name="per_page" class="h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    @foreach ([10, 20, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <a href="{{ route('admin.users') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-muted hover:bg-soft">Limpiar</a>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-brand/90">Aplicar filtros</button>
            </div>
        </div>
    </form>

    <x-ui.alert tone="info" class="mb-5">
        Sección de consulta: la identidad, el departamento, los roles y los permisos se administran únicamente en el sistema central de Accesos.
    </x-ui.alert>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Usuario</th>
                        <th class="px-5 py-3 font-semibold">Departamento</th>
                        <th class="px-5 py-3 font-semibold">Roles</th>
                        <th class="px-5 py-3 text-center font-semibold">Permisos</th>
                        <th class="px-5 py-3 text-center font-semibold">Archivos</th>
                        <th class="px-5 py-3 font-semibold">Almacenamiento</th>
                        <th class="px-5 py-3 font-semibold">Último acceso</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($users as $listedUser)
                        <tr class="text-sm hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <span class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand/8">
                                        <x-ui.icon name="user" :size="18" alt="" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-semibold">{{ trim("{$listedUser->name} {$listedUser->last_name}") }}</span>
                                        <span class="block text-xs text-muted">{{ $listedUser->email }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $listedUser->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex max-w-[220px] flex-wrap gap-1.5">
                                    @forelse ($listedUser->roles as $role)
                                        <span class="rounded-full bg-brand/8 px-2.5 py-1 text-[10px] font-semibold text-brand dark:text-white">
                                            {{ $role->display_name ?: $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-muted">Sin roles</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="block font-semibold">{{ number_format($listedUser->permissions_count) }}</span>
                                <span class="text-[11px] text-muted">efectivos</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="block font-semibold">{{ number_format($listedUser->files_count) }}</span>
                                <span class="text-[11px] text-muted">{{ number_format($listedUser->trashed_files_count) }} en papelera</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-semibold">{{ $listedUser->active_storage }}</span>
                                <span class="text-[11px] text-muted">{{ $listedUser->trashed_storage }} en papelera</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="block text-xs font-medium">{{ $listedUser->last_login_at?->format('d/m/Y H:i') ?? 'Sin registro' }}</span>
                                <span class="text-[11px] text-muted">
                                    Sincronizado {{ $listedUser->last_synced_at?->diffForHumans() ?? 'nunca' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $listedUser->active,
                                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $listedUser->active,
                                ])>{{ $listedUser->active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.users.show', $listedUser) }}" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Consultar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No encontramos usuarios</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros o espera la próxima sincronización con Accesos.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $users->onEachSide(1)->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
