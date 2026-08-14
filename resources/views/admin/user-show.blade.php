@php
    $listedUserName = trim("{$listedUser->name} {$listedUser->last_name}");
@endphp

<x-layouts.admin :title="$listedUserName" :user="$user">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('admin.users') }}" class="mb-2 inline-flex text-xs font-semibold text-brand hover:underline dark:text-gold">← Volver a usuarios</a>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-extrabold">{{ $listedUserName }}</h2>
                <span @class([
                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $listedUser->active,
                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $listedUser->active,
                ])>{{ $listedUser->active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <p class="mt-1 text-sm text-muted">{{ $listedUser->email }} · {{ $listedUser->department?->name ?? 'Sin departamento' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($listedUser->department)
                <a href="{{ route('admin.departments.show', $listedUser->department) }}" class="inline-flex h-10 items-center rounded-lg border border-line bg-surface px-4 text-sm font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Ver departamento</a>
            @endif
            <a href="{{ route('admin.files', ['user_id' => $listedUser->id]) }}" class="inline-flex h-10 items-center rounded-lg bg-brand px-4 text-sm font-semibold text-white hover:bg-brand/90">Ver todos sus archivos</a>
        </div>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Archivos activos</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['active_files']) }}</strong>
            <span class="text-xs text-muted">{{ number_format($summary['trashed_files']) }} en papelera</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Archivos totales</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['total_files']) }}</strong>
            <span class="text-xs text-muted">Activos y eliminados</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Espacio activo</span>
            <strong class="mt-2 block text-2xl">{{ $summary['active_storage'] }}</strong>
            <span class="text-xs text-muted">{{ $summary['trashed_storage'] }} en papelera</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Almacenamiento retenido</span>
            <strong class="mt-2 block text-2xl">{{ $summary['total_storage'] }}</strong>
            <span class="text-xs text-muted">Suma de activo y papelera</span>
        </article>
    </section>

    <section class="mb-5 grid gap-4 lg:grid-cols-[1fr_1.4fr]">
        <article class="rounded-xl border border-line bg-surface p-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Identidad y sincronización</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Identificador externo</dt>
                    <dd class="mt-1 break-all text-sm font-medium">{{ $listedUser->external_id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Departamento</dt>
                    <dd class="mt-1 text-sm font-medium">
                        {{ $listedUser->department?->name ?? 'Sin departamento' }}
                        @if ($listedUser->department?->abbreviation)
                            <span class="text-muted">({{ $listedUser->department->abbreviation }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Último inicio de sesión</dt>
                    <dd class="mt-1 text-sm font-medium">
                        {{ $listedUser->last_login_at?->format('d/m/Y H:i') ?? 'Sin registro' }}
                        @if ($listedUser->last_login_at)
                            <span class="block text-xs text-muted">{{ $listedUser->last_login_at->diffForHumans() }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Última sincronización</dt>
                    <dd class="mt-1 text-sm font-medium">
                        {{ $listedUser->last_synced_at?->format('d/m/Y H:i') ?? 'Sin sincronizar' }}
                        @if ($listedUser->last_synced_at)
                            <span class="block text-xs text-muted">{{ $listedUser->last_synced_at->diffForHumans() }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <x-ui.alert tone="info" class="mt-4">
                La identidad, el departamento, los roles y los permisos de este usuario sólo se modifican en el sistema central de Accesos.
            </x-ui.alert>
        </article>

        <article class="rounded-xl border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Roles y permisos efectivos</h3>
                <span class="text-xs font-semibold text-muted">{{ number_format($listedUser->permissions->count()) }} permisos</span>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold text-muted">Roles informativos</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse ($listedUser->roles as $role)
                        <span class="rounded-full bg-brand/8 px-2.5 py-1 text-[11px] font-semibold text-brand dark:text-white">{{ $role->display_name ?: $role->name }}</span>
                    @empty
                        <span class="text-xs text-muted">Este usuario no tiene roles asignados.</span>
                    @endforelse
                </div>
            </div>

            <div class="mt-5">
                <p class="text-xs font-semibold text-muted">Permisos efectivos</p>
                <div class="mt-2 flex max-h-64 flex-wrap gap-1.5 overflow-y-auto">
                    @forelse ($listedUser->permissions as $permission)
                        <span class="rounded-full border border-line px-2.5 py-1 text-[11px] font-medium" title="{{ $permission->display_name ?: $permission->name }}">{{ $permission->name }}</span>
                    @empty
                        <span class="text-xs text-muted">Este usuario no tiene permisos efectivos sobre la nube.</span>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-xl border border-line bg-surface">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
            <div>
                <h3 class="font-bold">Archivos del usuario</h3>
                <p class="mt-0.5 text-xs text-muted">Incluye archivos activos y en papelera; no se muestran rutas físicas.</p>
            </div>
            <span class="text-xs font-semibold text-muted">{{ number_format($userFiles->total()) }} en total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3">Archivo</th>
                        <th class="px-5 py-3">Departamento</th>
                        <th class="px-5 py-3">Clasificación</th>
                        <th class="px-5 py-3">Tamaño</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($userFiles as $file)
                        <tr class="hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <strong class="block max-w-[300px] truncate">{{ $file->display_name }}</strong>
                                <span class="text-xs text-muted">{{ $file->folder?->name ?? 'Raíz' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ $file->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5">{{ $file->visibility->label() }}</td>
                            <td class="px-5 py-3.5">{{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 1) }}</td>
                            <td class="px-5 py-3.5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => ! $file->trashed(),
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $file->trashed(),
                                ])>{{ $file->trashed() ? 'En papelera' : 'Activo' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.files.show', $file->id) }}" class="text-xs font-semibold text-brand hover:underline dark:text-gold">Metadatos</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">Este usuario todavía no tiene archivos en la nube.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($userFiles->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $userFiles->onEachSide(1)->links() }}</div>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="border-b border-line px-5 py-4">
            <h3 class="font-bold">Actividad reciente</h3>
            <p class="mt-0.5 text-xs text-muted">Últimos eventos auditados de este usuario.</p>
        </div>
        <div class="divide-y divide-line">
            @forelse ($recentActivity as $log)
                <div class="flex flex-col gap-1 px-5 py-3.5 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        <strong class="font-semibold">{{ $log->action }}</strong>
                        <span class="ml-2 text-xs text-muted">{{ $log->ip_address ?? 'Sin IP registrada' }}</span>
                    </span>
                    <time datetime="{{ $log->created_at?->toIso8601String() }}" class="text-xs text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</time>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-muted">No hay actividad auditada para este usuario.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
