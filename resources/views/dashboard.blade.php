<x-layouts.app title="Inicio" :user="$user" :permissions="$permissions">
    @php
        $isAdministrator = in_array('nube_administracion_administrar', $permissions, true);
        $canUploadPrivate = $isAdministrator
            || in_array('nube.archivos.subir', $permissions, true)
            || in_array('nube_mis_archivos_subir', $permissions, true);
        $canCreatePrivateFolder = $isAdministrator
            || in_array('nube_archivos_crear_carpeta', $permissions, true)
            || in_array('nube_mis_archivos_crear_carpeta', $permissions, true);
    @endphp

    <section class="mb-5 lg:mb-6">
        <h2 class="text-[22px] font-bold text-brand dark:text-white lg:text-[28px] lg:text-ink lg:dark:text-white">Buenos días, {{ $user['first_name'] }}</h2>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs lg:text-sm">
            <span class="font-semibold text-gold-ink dark:text-gold lg:text-brand lg:dark:text-white">Departamento de {{ $user['department'] }}</span>
            <span class="size-1 rounded-full bg-gold"></span>
            <span class="text-muted">{{ $today }}</span>
        </div>
        <p class="mt-1 hidden text-[13px] text-muted lg:block">Aquí tienes un resumen de tu actividad y accesos rápidos.</p>
    </section>

    <section aria-labelledby="quick-actions-title" class="mb-5 lg:mb-6">
        <h3 id="quick-actions-title" class="mb-2.5 text-sm font-bold uppercase text-ink lg:sr-only">Acciones rápidas</h3>
        <div class="grid grid-cols-2 gap-2 lg:flex lg:flex-wrap lg:gap-3">
            @if ($canUploadPrivate)
                <a href="{{ route('folders.mine', ['open' => 'upload']) }}" class="inline-flex h-12 items-center justify-start gap-2 rounded-[10px] bg-brand px-3 text-sm font-semibold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold lg:h-auto lg:justify-center lg:px-5 lg:py-2.5">
                    <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                        <x-ui.icon name="upload-mobile" :size="16" alt="" />
                    </span>
                    <x-ui.icon name="upload-cloud" :size="18" alt="" class="hidden lg:block" />
                    <span>Subir archivo</span>
                </a>
            @endif
            @if ($canCreatePrivateFolder)
                <a href="{{ route('folders.mine', ['open' => 'folder']) }}" class="inline-flex h-12 items-center justify-start gap-2 rounded-[10px] border border-gold bg-warm px-3 text-sm font-semibold text-brand transition hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold dark:text-white lg:h-auto lg:justify-center lg:border-line lg:bg-surface lg:px-5 lg:py-2.5 lg:text-ink">
                    <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                        <x-ui.icon name="folder-plus-mobile" :size="16" alt="" />
                    </span>
                    <x-ui.icon name="folder-plus" :size="18" alt="" class="hidden lg:block" />
                    <span>Nueva carpeta</span>
                </a>
            @endif
            @if (in_array('nube_mis_archivos_ver', $permissions, true) || in_array('nube_administracion_administrar', $permissions, true))
                <a href="{{ route('folders.mine') }}" class="inline-flex h-12 items-center justify-start gap-2 rounded-[10px] border border-gold bg-warm px-3 text-sm font-semibold text-brand transition hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold dark:text-white lg:h-auto lg:justify-center lg:border-line lg:bg-surface lg:px-5 lg:py-2.5 lg:text-ink">
                    <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                        <x-ui.icon name="folder-mobile" :size="16" alt="" />
                    </span>
                    <x-ui.icon name="folder" :size="18" alt="" class="hidden lg:block" />
                    <span>Mis archivos</span>
                </a>
            @endif
            @if (in_array('nube_departamento_ver', $permissions, true) || in_array('nube_administracion_administrar', $permissions, true))
                <a href="{{ route('folders.department') }}" class="inline-flex h-12 items-center justify-start gap-2 rounded-[10px] border border-gold bg-warm px-3 text-sm font-semibold text-brand transition hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold dark:text-white lg:h-auto lg:justify-center lg:border-line lg:bg-surface lg:px-5 lg:py-2.5 lg:text-ink">
                    <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                        <x-ui.icon name="users-mobile" :size="16" alt="" />
                    </span>
                    <x-ui.icon name="building" :size="18" alt="" class="hidden lg:block" />
                    <span class="lg:hidden">Departamento</span>
                    <span class="hidden lg:inline">Archivos del departamento</span>
                </a>
            @endif
        </div>
    </section>

    <section aria-label="Indicadores" class="-mx-5 mb-5 overflow-x-auto px-5 pb-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:mb-6 lg:overflow-visible lg:px-0">
        <div class="flex min-w-max gap-2 lg:grid lg:min-w-0 lg:grid-cols-4 lg:gap-4">
            @foreach ($indicators as $indicator)
                <article class="w-[115px] rounded-xl border border-line bg-surface p-3 lg:w-auto lg:p-5">
                    <div class="mb-2 flex items-center justify-between lg:mb-3">
                        <h3 class="truncate text-[11px] font-semibold text-muted lg:text-xs lg:uppercase">{{ $indicator['label'] }}</h3>
                        <span class="hidden size-8 items-center justify-center rounded-lg bg-brand/5 lg:flex">
                            <x-ui.icon :name="$indicator['icon']" :size="18" alt="" />
                        </span>
                    </div>
                    <p class="whitespace-nowrap text-lg font-bold text-brand dark:text-white lg:text-xl lg:text-ink lg:dark:text-white">{{ $indicator['value'] }}</p>
                    <p class="mt-1 hidden truncate text-[11px] text-muted lg:block">{{ $indicator['hint'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="recent-files-title" class="mb-5 lg:mb-6">
        <div class="mb-3 flex items-center justify-between lg:mb-0 lg:rounded-t-xl lg:border lg:border-line lg:bg-surface lg:p-5">
            <h3 id="recent-files-title" class="text-sm font-bold uppercase text-ink lg:text-[15px] lg:normal-case">Archivos Recientes</h3>
            @if (in_array('nube_mis_archivos_ver', $permissions, true) || in_array('nube_administracion_administrar', $permissions, true))
                <a href="{{ route('folders.mine') }}" class="text-xs font-semibold text-gold-ink underline-offset-4 hover:underline dark:text-gold lg:text-[13px]">Ver todos</a>
            @endif
        </div>

        <div class="space-y-2 lg:hidden">
            @forelse (array_slice($files, 0, 3) as $file)
                <article class="flex items-center gap-3 rounded-[10px] border border-line bg-surface p-3">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-blue-50' => $file['tone'] === 'private',
                        'bg-emerald-50' => $file['tone'] === 'collaborative',
                        'bg-red-50' => $file['tone'] === 'public',
                    ])>
                        <x-ui.icon :name="$file['icon']" :size="20" alt="" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] font-semibold">{{ $file['name'] }}</span>
                        <span class="mt-1 flex items-center gap-1.5 text-[11px] text-muted">
                            <span>{{ $file['modified'] }}</span>
                            <span class="size-[3px] rounded-full bg-[#9ca3af]"></span>
                            <span>{{ $file['size'] }}</span>
                        </span>
                    </span>
                    <span @class([
                        'rounded-md px-2 py-1 text-[10px] font-semibold',
                        'bg-brand/10 text-brand dark:bg-white/10 dark:text-white' => $file['tone'] === 'private',
                        'bg-gold/10 text-gold-ink dark:text-gold' => $file['tone'] === 'collaborative',
                        'bg-emerald-50 text-emerald-600' => $file['tone'] === 'public',
                    ])>{{ $file['visibility'] }}</span>
                </article>
            @empty
                <div class="rounded-[10px] border border-dashed border-line bg-surface px-4 py-8 text-center text-sm text-muted">
                    No hay archivos recientes disponibles.
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-hidden rounded-b-xl border-x border-b border-line bg-surface lg:block">
            <table class="w-full table-fixed text-left">
                <thead class="bg-surface-alt text-[10px] uppercase text-muted">
                    <tr>
                        <th class="w-[30%] px-5 py-3 font-semibold">Nombre</th>
                        <th class="w-[16%] px-5 py-3 font-semibold">Clasificación</th>
                        <th class="w-[20%] px-5 py-3 font-semibold">Ubicación</th>
                        <th class="w-[15%] px-5 py-3 font-semibold">Modificado</th>
                        <th class="w-[11%] px-5 py-3 font-semibold">Tamaño</th>
                        <th class="w-[8%] px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($files as $file)
                        <tr class="text-[13px] transition hover:bg-warm">
                            <td class="px-5 py-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-ui.icon :name="$file['icon']" :size="20" alt="" />
                                    <span class="truncate font-medium text-ink">{{ $file['name'] }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-md px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-brand/10 text-brand dark:bg-white/10 dark:text-white' => $file['tone'] === 'private',
                                    'bg-blue-50 text-blue-600' => $file['tone'] === 'collaborative',
                                    'bg-emerald-50 text-emerald-600' => $file['tone'] === 'public',
                                ])>{{ $file['visibility'] }}</span>
                            </td>
                            <td class="truncate px-5 py-3 text-muted">{{ $file['location'] }}</td>
                            <td class="px-5 py-3 text-muted">{{ $file['modified'] }}</td>
                            <td class="px-5 py-3 text-muted">{{ $file['size'] }}</td>
                            <td class="px-5 py-3">
                                @if ($file['download_url'])
                                    <a href="{{ $file['download_url'] }}" class="ml-auto flex size-8 items-center justify-center rounded-full hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" aria-label="Descargar {{ $file['name'] }}">
                                        <x-ui.icon name="arrow-down" :size="16" alt="" />
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">
                                No hay archivos recientes disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section aria-labelledby="recent-folders-title" class="hidden overflow-hidden rounded-xl border border-line bg-surface lg:block">
            <div class="flex items-center justify-between border-b border-line p-5">
                <h3 id="recent-folders-title" class="text-[15px] font-bold">Carpetas Recientes</h3>
                @if (in_array('nube_mis_archivos_ver', $permissions, true) || $isAdministrator)
                    <a href="{{ route('folders.mine') }}" class="text-[13px] font-semibold text-gold-ink underline dark:text-gold">Ver todas</a>
                @endif
            </div>
            <div class="divide-y divide-line">
                @forelse ($folders as $folder)
                    <a href="{{ $folder['url'] }}" class="flex items-center justify-between px-5 py-3 hover:bg-warm focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand">
                        <span class="flex items-center gap-3">
                            <x-ui.icon name="folder" :size="20" alt="" />
                            <span>
                                <span class="block text-sm font-semibold">{{ $folder['name'] }}</span>
                                <span class="block text-xs text-muted">{{ $folder['location'] }}</span>
                            </span>
                        </span>
                        <span class="flex items-center gap-3 text-xs text-muted">
                            {{ $folder['time'] }}
                            <x-ui.icon name="chevron-right" :size="16" alt="" />
                        </span>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-muted">No hay carpetas recientes disponibles.</p>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="activity-title">
            <h3 id="activity-title" class="mb-3 text-sm font-bold uppercase text-ink lg:hidden">Actividad Reciente</h3>
            <div class="overflow-hidden rounded-xl border border-line bg-surface">
                <div class="hidden border-b border-line p-5 lg:block">
                    <h3 class="text-[15px] font-bold">Actividad Reciente</h3>
                </div>
                <div class="divide-y divide-line px-4 py-2 lg:px-0 lg:py-0">
                    @forelse ($activities as $activity)
                        <div class="flex items-center gap-3 py-2.5 lg:px-5 lg:py-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-soft">
                                <x-ui.icon :name="$activity['icon']" :size="14" alt="" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-[13px] font-medium">{{ $activity['text'] }}</span>
                                <span class="block text-[11px] text-muted">{{ $activity['time'] }}</span>
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-muted">Tu actividad reciente aparecerá aquí.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
