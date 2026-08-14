@php
    $probeTone = match ($accessApi['probe_state']) {
        'online' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'degraded' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'offline' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        default => 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-white/60',
    };
    $probeLabel = match ($accessApi['probe_state']) {
        'online' => 'En línea',
        'degraded' => 'Con incidencias',
        'offline' => 'Sin conexión',
        default => 'Sin comprobar',
    };
@endphp

<x-layouts.admin title="Configuración" :user="$user">
    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('admin_settings_error'))
        <x-ui.alert class="mb-5">{{ session('admin_settings_error') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Configuración operativa</h2>
            <p class="mt-1 text-sm text-muted">Estado técnico del entorno actual. Los valores se modifican fuera de la aplicación, mediante el proceso de despliegue.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-semibold text-muted">
            <x-ui.icon name="eye" :size="14" alt="" />
            Solo lectura
        </span>
    </div>

    <section class="mb-5 grid gap-4 lg:grid-cols-2" aria-label="Cargas y almacenamiento">
        <article class="rounded-xl border border-line bg-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand/8">
                    <x-ui.icon name="upload-cloud" :size="21" alt="" />
                </span>
                <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Cargas</h3>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Tamaño máximo por archivo</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $uploads['max_size'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Alineación con PHP</dt>
                    <dd class="mt-1">
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $uploads['aligned'],
                            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => ! $uploads['aligned'],
                        ])>{{ $uploads['aligned'] ? 'Correcta' : 'Revisar' }}</span>
                        <span class="mt-1 block text-xs text-muted">
                            upload_max_filesize {{ $uploads['php_upload_max'] }} · post_max_size {{ $uploads['php_post_max'] }}
                        </span>
                    </dd>
                </div>
            </dl>
            <div class="mt-4">
                <p class="text-xs font-semibold text-muted">Extensiones permitidas ({{ count($uploads['extensions']) }})</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($uploads['extensions'] as $extension)
                        <span class="rounded-full border border-line px-2.5 py-1 text-[11px] font-medium uppercase">{{ $extension }}</span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-muted">Se validan además {{ $uploads['mime_types_count'] }} tipos MIME declarados.</p>
            </div>
        </article>

        <article class="rounded-xl border border-line bg-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand/8">
                    <x-ui.icon name="folder-lock" :size="21" alt="" />
                </span>
                <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Almacenamiento</h3>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Disco</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $storage['disk_name'] }} ({{ $storage['disk'] }}, {{ $storage['visibility'] }})</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Ruta raíz configurada</dt>
                    <dd class="mt-1 break-all font-mono text-xs font-medium">{{ $storage['root'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Espacio utilizado</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $storage['used_storage'] }}</dd>
                    <dd class="text-xs text-muted">{{ $storage['active_storage'] }} activo · {{ $storage['trashed_storage'] }} en papelera</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Espacio disponible</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $storage['free_storage'] }}</dd>
                    <dd class="text-xs text-muted">
                        @if ($storage['used_percentage'] !== null)
                            {{ $storage['used_percentage'] }}% del volumen ocupado
                        @else
                            Capacidad del volumen no disponible
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 space-y-2">
                <p class="flex items-center gap-2 text-xs {{ $storage['outside_public'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    <span aria-hidden="true">{{ $storage['outside_public'] ? '✓' : '✕' }}</span>
                    El disco privado {{ $storage['outside_public'] ? 'está fuera de public' : 'quedó dentro de public' }}
                </p>
                <p class="flex items-center gap-2 text-xs {{ $storage['public_link_absent'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    <span aria-hidden="true">{{ $storage['public_link_absent'] ? '✓' : '✕' }}</span>
                    {{ $storage['public_link_absent'] ? 'No existe enlace público de almacenamiento' : 'Existe un enlace público de almacenamiento' }}
                </p>
            </div>
        </article>
    </section>

    <section class="mb-5 grid gap-4 lg:grid-cols-2" aria-label="Papelera y API de Accesos">
        <article class="rounded-xl border border-line bg-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand/8">
                    <x-ui.icon name="trash" :size="21" alt="" />
                </span>
                <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Papelera y publicación</h3>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Retención</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $trash['retention_days'] }} días</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Purga automática</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $trash['purge_schedule'] }}</dd>
                    <dd class="font-mono text-xs text-muted">{{ $trash['purge_command'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Archivos pendientes de purga</dt>
                    <dd class="mt-1 text-lg font-bold">{{ number_format($trash['pending_purge']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Carpetas eliminadas</dt>
                    <dd class="mt-1 text-sm font-medium">Purga manual</dd>
                    <dd class="text-xs text-muted">No las alcanza la purga programada</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Clasificaciones disponibles</dt>
                    <dd class="mt-2 flex flex-wrap gap-1.5">
                        @foreach (\App\Enums\FileVisibility::cases() as $visibility)
                            <span class="rounded-full border border-line px-2.5 py-1 text-[11px] font-medium">{{ $visibility->label() }}</span>
                        @endforeach
                    </dd>
                    <dd class="mt-2 text-xs text-muted">Publicar o reclasificar exige el permiso correspondiente de Accesos.</dd>
                </div>
            </dl>
        </article>

        <article class="rounded-xl border border-line bg-surface p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand/8">
                    <x-ui.icon name="shield" :size="21" alt="" />
                </span>
                <h3 class="text-sm font-bold uppercase tracking-wide text-muted">API de Accesos</h3>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Servidor</dt>
                    <dd class="mt-1 break-all text-sm font-medium">{{ $accessApi['host'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Clave del sistema</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $accessApi['system_key_configured'] ? 'Configurada' : 'Pendiente' }}</dd>
                    <dd class="text-xs text-muted">Su valor nunca se muestra</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Tiempo de espera</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $accessApi['timeout'] }} segundos</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Revalidación de sesión</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $accessApi['session_check_interval'] }} segundos</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Última validación de tu sesión</dt>
                    <dd class="mt-1 text-sm font-medium">
                        {{ $accessApi['session_validated_at']?->format('d/m/Y H:i:s') ?? 'Sin registro' }}
                        <span class="block text-xs text-muted">Tu sesión sigue activa, así que el API respondió correctamente en esa validación.</span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Comprobación en vivo</dt>
                    <dd class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $probeTone }}">{{ $probeLabel }}</span>
                        @if ($accessApi['probe_at'])
                            <span class="text-xs text-muted">{{ $accessApi['probe_at']->format('d/m/Y H:i') }}</span>
                        @endif
                    </dd>
                    @if ($accessApi['probe_message'])
                        <dd class="mt-1 text-xs text-muted">{{ $accessApi['probe_message'] }}</dd>
                    @endif
                </div>
            </dl>
            <form action="{{ route('admin.settings.check') }}" method="POST" class="mt-4">
                @csrf
                <x-ui.button type="submit" variant="secondary">Comprobar conexión ahora</x-ui.button>
            </form>
            <p class="mt-2 text-xs text-muted">La comprobación consulta el API con el token de tu sesión. El panel no la ejecuta automáticamente para no generar tráfico externo en cada carga.</p>
        </article>
    </section>

    <section class="rounded-xl border border-gold/35 bg-gold/10 p-5">
        <div class="flex items-start gap-3">
            <x-ui.icon name="lock" :size="22" alt="" />
            <div>
                <h2 class="text-sm font-bold">Secretos protegidos</h2>
                <p class="mt-1 max-w-3xl text-xs leading-5 text-muted">
                    Este panel no expone tokens, claves del sistema de Accesos, contraseñas ni las rutas físicas de los archivos. La ruta raíz del disco se muestra relativa al proyecto porque es configuración del despliegue, no la ubicación de un recurso concreto. Los cambios de configuración se realizan fuera de la aplicación.
                </p>
            </div>
        </div>
    </section>
</x-layouts.admin>
