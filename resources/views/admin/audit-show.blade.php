@php
    $resourceReference = $log->resource_name ?? ($log->resource_id ?: '—');
@endphp

<x-layouts.admin :title="$log->action" :user="$user">
    <div class="mb-5">
        <a href="{{ route('admin.audit') }}" class="mb-2 inline-flex text-xs font-semibold text-brand hover:underline dark:text-gold">← Volver a la bitácora</a>
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-extrabold">{{ $log->action }}</h2>
            <span @class([
                'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $log->administrative,
                'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60' => ! $log->administrative,
            ])>{{ $log->administrative ? 'Acción administrativa' : 'Acción de usuario' }}</span>
        </div>
        <p class="mt-1 text-sm text-muted">Evento #{{ $log->id }} · {{ $log->created_at?->format('d/m/Y H:i:s') }}</p>
    </div>

    <section class="mb-5 grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-line bg-surface p-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Actor y origen</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Usuario</dt>
                    <dd class="mt-1 text-sm font-medium">{{ trim("{$log->user?->name} {$log->user?->last_name}") ?: 'Sistema' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Correo</dt>
                    <dd class="mt-1 break-all text-sm font-medium">{{ $log->user?->email ?? 'Proceso automático' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Departamento</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $log->user?->department?->name ?? 'Sin departamento' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Dirección IP</dt>
                    <dd class="mt-1 font-mono text-sm font-medium">{{ $log->ip_address ?: 'No disponible' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Agente de usuario</dt>
                    <dd class="mt-1 break-all text-xs font-medium text-muted">{{ $log->user_agent ?: 'No disponible' }}</dd>
                </div>
            </dl>
            @if ($log->user)
                <a href="{{ route('admin.users.show', $log->user) }}" class="mt-4 inline-flex h-10 items-center rounded-lg border border-line px-4 text-sm font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Ver al usuario</a>
            @endif
        </article>

        <article class="rounded-xl border border-line bg-surface p-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Recurso afectado</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Tipo</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $log->resource_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Nombre actual</dt>
                    <dd class="mt-1 break-all text-sm font-medium">{{ $log->resource_name ?? 'No disponible' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Identificador</dt>
                    <dd class="mt-1 break-all font-mono text-xs font-medium">{{ $log->resource_id ?: '—' }}</dd>
                </div>
            </dl>
            @if ($log->resource_type === \App\Models\File::class && $log->resource_id)
                <a href="{{ route('admin.files.show', $log->resource_id) }}" class="mt-4 inline-flex h-10 items-center rounded-lg border border-line px-4 text-sm font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Ver metadatos del archivo</a>
            @endif
            <x-ui.alert tone="info" class="mt-4">
                Este registro es inmutable: la plataforma no permite editarlo ni eliminarlo.
            </x-ui.alert>
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-xl border border-line bg-surface">
        <div class="border-b border-line px-5 py-4">
            <h3 class="font-bold">Detalles del evento</h3>
            <p class="mt-0.5 text-xs text-muted">Contenido del campo <span class="font-mono">details</span>. Las rutas físicas, nombres almacenados, checksums y secretos se muestran como <span class="font-semibold">[OCULTO]</span>.</p>
        </div>
        @if ($details === [])
            <p class="px-5 py-10 text-center text-sm text-muted">Este evento no registró detalles adicionales.</p>
        @else
            <div class="overflow-x-auto px-5 py-4">
                <pre class="whitespace-pre-wrap break-words font-mono text-xs leading-6 text-ink">{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="border-b border-line px-5 py-4">
            <h3 class="font-bold">Otros eventos del mismo recurso</h3>
            <p class="mt-0.5 text-xs text-muted">Historial reciente de {{ mb_strtolower($log->resource_label) }} {{ $resourceReference }}.</p>
        </div>
        <div class="divide-y divide-line">
            @forelse ($relatedLogs as $related)
                <a href="{{ route('admin.audit.show', $related) }}" class="flex flex-col gap-1 px-5 py-3.5 text-sm hover:bg-warm sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        <strong class="font-semibold">{{ $related->action }}</strong>
                        <span class="ml-2 text-xs text-muted">{{ trim("{$related->user?->name} {$related->user?->last_name}") ?: 'Sistema' }}</span>
                    </span>
                    <time datetime="{{ $related->created_at?->toIso8601String() }}" class="text-xs text-muted">{{ $related->created_at?->format('d/m/Y H:i') }}</time>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-muted">No hay otros eventos registrados para este recurso.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
