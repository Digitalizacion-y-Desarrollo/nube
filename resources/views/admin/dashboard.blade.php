<x-layouts.admin title="Resumen" :user="$user">
    <section class="mb-6 overflow-hidden rounded-2xl bg-brand p-5 text-white shadow-lg shadow-brand/10 sm:p-7">
        <div class="relative z-10 max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-gold">Centro de control</p>
            <h2 class="text-2xl font-extrabold sm:text-3xl">Administración de Nube Municipal</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/75">
                Consulta el estado general de usuarios, departamentos, archivos y actividad del sistema desde un espacio separado de tu nube personal.
            </p>
        </div>
    </section>

    <section aria-label="Indicadores administrativos" class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($summary as $item)
            <article class="rounded-xl border border-line bg-surface p-5">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-brand/8">
                        <x-ui.icon :name="$item['icon']" :size="21" alt="" />
                    </span>
                    <span class="rounded-full bg-soft px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-muted">Global</span>
                </div>
                <p class="text-2xl font-extrabold text-ink">{{ $item['value'] }}</p>
                <h3 class="mt-1 text-sm font-semibold">{{ $item['label'] }}</h3>
                <p class="mt-1 text-xs text-muted">{{ $item['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mb-6 rounded-xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="storage-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="storage-title" class="font-bold">Estado del almacenamiento</h2>
                <p class="mt-1 text-xs text-muted">Espacio retenido por archivos activos y archivos que todavía permanecen en la papelera.</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-soft px-3 py-1 text-xs font-semibold text-muted">
                Total: {{ $storage['total'] }}
            </span>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <article class="rounded-lg bg-soft p-4">
                <p class="text-xs font-semibold text-muted">Archivos activos</p>
                <p class="mt-1 text-xl font-extrabold">{{ $storage['active'] }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $storage['active_percent'] }}% del espacio retenido</p>
            </article>
            <article class="rounded-lg bg-red-50 p-4 dark:bg-red-500/10">
                <p class="text-xs font-semibold text-red-700 dark:text-red-300">En papelera</p>
                <p class="mt-1 text-xl font-extrabold">{{ $storage['deleted'] }}</p>
                <p class="mt-1 text-[11px] text-red-700/70 dark:text-red-300/70">{{ $storage['deleted_percent'] }}% pendiente de purga</p>
            </article>
            <article class="rounded-lg bg-brand p-4 text-white">
                <p class="text-xs font-semibold text-white/70">Total utilizado</p>
                <p class="mt-1 text-xl font-extrabold">{{ $storage['total'] }}</p>
                <p class="mt-1 text-[11px] text-white/65">Activos más papelera</p>
            </article>
        </div>

        <div class="mt-5 flex h-3 overflow-hidden rounded-full bg-soft" aria-label="Distribución del espacio utilizado">
            @if ($storage['active_percent'] > 0)
                <span class="h-full bg-brand" style="width: {{ $storage['active_percent'] }}%" title="Activos: {{ $storage['active'] }}"></span>
            @endif
            @if ($storage['deleted_percent'] > 0)
                <span class="h-full bg-red-400" style="width: {{ $storage['deleted_percent'] }}%" title="Papelera: {{ $storage['deleted'] }}"></span>
            @endif
        </div>
        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-[11px] text-muted">
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-brand"></span>Activos</span>
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-red-400"></span>Papelera</span>
        </div>
    </section>

    <div class="mb-6 grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-xl border border-line bg-surface" aria-labelledby="departments-consumption-title">
            <div class="border-b border-line px-5 py-4">
                <h2 id="departments-consumption-title" class="font-bold">Departamentos con mayor consumo</h2>
                <p class="mt-0.5 text-xs text-muted">Ordenados por espacio total retenido.</p>
            </div>
            <ol class="divide-y divide-line">
                @forelse ($topDepartments as $department)
                    <li class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand/8 text-xs font-extrabold text-brand dark:text-white">{{ $department['rank'] }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $department['name'] }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $department['meta'] }} · {{ number_format($department['files_count']) }} archivos</span>
                                    </span>
                                    <strong class="whitespace-nowrap text-sm">{{ $department['total'] }}</strong>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-soft">
                                    <span class="block h-full rounded-full bg-brand" style="width: {{ $department['percent'] }}%"></span>
                                </div>
                                <p class="mt-1.5 text-[11px] text-muted">{{ $department['active'] }} activos · {{ $department['deleted'] }} en papelera</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">Aún no hay consumo por departamento.</li>
                @endforelse
            </ol>
        </section>

        <section class="overflow-hidden rounded-xl border border-line bg-surface" aria-labelledby="users-consumption-title">
            <div class="border-b border-line px-5 py-4">
                <h2 id="users-consumption-title" class="font-bold">Usuarios con mayor consumo</h2>
                <p class="mt-0.5 text-xs text-muted">Incluye sus archivos activos y eliminados.</p>
            </div>
            <ol class="divide-y divide-line">
                @forelse ($topUsers as $listedUser)
                    <li class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gold/15 text-xs font-extrabold text-gold-ink dark:text-gold">{{ $listedUser['rank'] }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $listedUser['name'] }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $listedUser['meta'] }} · {{ number_format($listedUser['files_count']) }} archivos</span>
                                    </span>
                                    <strong class="whitespace-nowrap text-sm">{{ $listedUser['total'] }}</strong>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-soft">
                                    <span class="block h-full rounded-full bg-gold" style="width: {{ $listedUser['percent'] }}%"></span>
                                </div>
                                <p class="mt-1.5 text-[11px] text-muted">{{ $listedUser['active'] }} activos · {{ $listedUser['deleted'] }} en papelera</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">Aún no hay consumo por usuario.</li>
                @endforelse
            </ol>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.65fr)]">
        <section class="overflow-hidden rounded-xl border border-line bg-surface" aria-labelledby="admin-activity-title">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <div>
                    <h2 id="admin-activity-title" class="font-bold">Actividad reciente</h2>
                    <p class="mt-0.5 text-xs text-muted">Últimos eventos registrados en todo el sistema.</p>
                </div>
                <a href="{{ route('admin.audit') }}" class="text-xs font-semibold text-gold-ink hover:underline dark:text-gold">Ver auditoría</a>
            </div>
            <div class="divide-y divide-line">
                @forelse ($recentActivity as $log)
                    <article class="flex items-center gap-3 px-5 py-3.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-soft">
                            <x-ui.icon name="clock" :size="17" alt="" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $log->action }}</span>
                            <span class="block truncate text-xs text-muted">
                                {{ trim("{$log->user?->name} {$log->user?->last_name}") ?: 'Sistema' }}
                                · {{ $log->resource_type ?: 'Sin recurso' }}
                            </span>
                        </span>
                        <time class="hidden text-xs text-muted sm:block" datetime="{{ $log->created_at?->toIso8601String() }}">
                            {{ $log->created_at?->diffForHumans() }}
                        </time>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-muted">Todavía no hay actividad registrada.</p>
                @endforelse
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-xl border border-line bg-surface p-5" aria-labelledby="classification-title">
                <h2 id="classification-title" class="font-bold">Archivos por clasificación</h2>
                <p class="mt-1 text-xs text-muted">Activos y eliminados para cada visibilidad.</p>
                <div class="mt-5 space-y-3">
                    @foreach ($visibility as $item)
                        <div class="rounded-lg bg-soft px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2 text-sm font-medium">
                                    <span @class([
                                        'size-2.5 rounded-full',
                                        'bg-blue-500' => $item['tone'] === 'private',
                                        'bg-gold' => $item['tone'] === 'collaborative',
                                        'bg-emerald-500' => $item['tone'] === 'public',
                                    ])></span>
                                    {{ $item['label'] }}
                                </span>
                                <strong class="text-sm">{{ number_format($item['total_count']) }}</strong>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3 text-[11px] text-muted">
                                <span>{{ number_format($item['active_count']) }} activos · {{ number_format($item['deleted_count']) }} en papelera</span>
                                <span>{{ $item['active_storage'] }}</span>
                            </div>
                            <div class="mt-2 h-1 overflow-hidden rounded-full bg-line">
                                <span @class([
                                    'block h-full rounded-full',
                                    'bg-blue-500' => $item['tone'] === 'private',
                                    'bg-gold' => $item['tone'] === 'collaborative',
                                    'bg-emerald-500' => $item['tone'] === 'public',
                                ]) style="width: {{ $item['active_percent'] }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-gold/35 bg-gold/10 p-5">
                <div class="flex items-start gap-3">
                    <x-ui.icon name="shield" :size="22" alt="" />
                    <div>
                        <h2 class="text-sm font-bold">Acceso por rol</h2>
                        <p class="mt-1 text-xs leading-5 text-muted">
                            Este panel está reservado al rol <strong class="text-ink">superuser</strong>. Las operaciones sobre archivos continúan sujetas a permisos y Policies.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
