<x-layouts.app title="Inicio" :user="$user">
    <section class="mb-5 lg:mb-6">
        <h2 class="text-[22px] font-bold text-brand lg:text-[28px] lg:text-[#1f2937]">Buenos días, {{ $user['first_name'] }}</h2>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs lg:text-sm">
            <span class="font-semibold text-gold lg:text-brand">Departamento de {{ $user['department'] }}</span>
            <span class="size-1 rounded-full bg-gold"></span>
            <span class="text-[#6b7280]">23 de julio de 2026</span>
        </div>
        <p class="mt-1 hidden text-[13px] text-[#6b7280] lg:block">Aquí tienes un resumen de tu actividad y accesos rápidos.</p>
    </section>

    <section aria-labelledby="quick-actions-title" class="mb-5 lg:mb-6">
        <h3 id="quick-actions-title" class="mb-2.5 text-sm font-bold uppercase text-[#1f2937] lg:sr-only">Acciones rápidas</h3>
        <div class="grid grid-cols-2 gap-2 lg:flex lg:flex-wrap lg:gap-3">
            <x-ui.button data-modal-open="upload-modal" class="h-12 justify-start px-3 lg:h-auto lg:justify-center lg:px-5">
                <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                    <x-ui.icon name="upload-mobile" :size="16" alt="" />
                </span>
                <x-ui.icon name="upload-cloud" :size="18" alt="" class="hidden lg:block" />
                <span>Subir archivo</span>
            </x-ui.button>
            <x-ui.button variant="outline" data-modal-open="folder-modal" class="h-12 justify-start px-3 lg:h-auto lg:justify-center lg:px-5">
                <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                    <x-ui.icon name="folder-plus-mobile" :size="16" alt="" />
                </span>
                <x-ui.icon name="folder-plus" :size="18" alt="" class="hidden lg:block" />
                <span>Nueva carpeta</span>
            </x-ui.button>
            <x-ui.button variant="outline" class="h-12 justify-start px-3 lg:h-auto lg:justify-center lg:border-[#eceef0] lg:bg-white lg:px-5 lg:text-[#1f2937]">
                <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                    <x-ui.icon name="folder-mobile" :size="16" alt="" />
                </span>
                <x-ui.icon name="folder" :size="18" alt="" class="hidden lg:block" />
                <span>Mis archivos</span>
            </x-ui.button>
            <x-ui.button variant="outline" class="h-12 justify-start px-3 lg:h-auto lg:justify-center lg:border-[#eceef0] lg:bg-white lg:px-5 lg:text-[#1f2937]">
                <span class="flex size-7 items-center justify-center rounded-md bg-brand lg:hidden">
                    <x-ui.icon name="users-mobile" :size="16" alt="" />
                </span>
                <x-ui.icon name="building" :size="18" alt="" class="hidden lg:block" />
                <span class="lg:hidden">Departamento</span>
                <span class="hidden lg:inline">Archivos del departamento</span>
            </x-ui.button>
        </div>
    </section>

    <section aria-label="Indicadores" class="-mx-5 mb-5 overflow-x-auto px-5 pb-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:mb-6 lg:overflow-visible lg:px-0">
        <div class="flex min-w-max gap-2 lg:grid lg:min-w-0 lg:grid-cols-4 lg:gap-4">
            @foreach ($indicators as $indicator)
                <article class="w-[115px] rounded-xl border border-[#e5e7eb] bg-white p-3 lg:w-auto lg:p-5">
                    <div class="mb-2 flex items-center justify-between lg:mb-3">
                        <h3 class="truncate text-[11px] font-semibold text-[#6b7280] lg:text-xs lg:uppercase">{{ $indicator['label'] }}</h3>
                        <span class="hidden size-8 items-center justify-center rounded-lg bg-brand/5 lg:flex">
                            <x-ui.icon :name="$indicator['icon']" :size="18" alt="" />
                        </span>
                    </div>
                    <p class="whitespace-nowrap text-lg font-bold text-brand lg:text-xl lg:text-[#1f2937]">{{ $indicator['value'] }}</p>
                    <p class="mt-1 hidden truncate text-[11px] text-[#6b7280] lg:block">{{ $indicator['hint'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="recent-files-title" class="mb-5 lg:mb-6">
        <div class="mb-3 flex items-center justify-between lg:mb-0 lg:rounded-t-xl lg:border lg:border-[#eceef0] lg:bg-white lg:p-5">
            <h3 id="recent-files-title" class="text-sm font-bold uppercase text-[#1f2937] lg:text-[15px] lg:normal-case">Archivos Recientes</h3>
            <a href="#" class="text-xs font-semibold text-gold underline-offset-4 hover:underline lg:text-[13px]">Ver todos</a>
        </div>

        <div class="space-y-2 lg:hidden">
            @foreach (array_slice($files, 0, 3) as $file)
                <article class="flex items-center gap-3 rounded-[10px] border border-[#f3f4f6] bg-white p-3">
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
                        <span class="mt-1 flex items-center gap-1.5 text-[11px] text-[#6b7280]">
                            <span>{{ $file['modified'] }}</span>
                            <span class="size-[3px] rounded-full bg-[#9ca3af]"></span>
                            <span>{{ $file['size'] }}</span>
                        </span>
                    </span>
                    <span @class([
                        'rounded-md px-2 py-1 text-[10px] font-semibold',
                        'bg-brand/10 text-brand' => $file['tone'] === 'private',
                        'bg-gold/10 text-gold' => $file['tone'] === 'collaborative',
                        'bg-emerald-50 text-emerald-600' => $file['tone'] === 'public',
                    ])>{{ $file['visibility'] }}</span>
                </article>
            @endforeach
        </div>

        <div class="hidden overflow-hidden rounded-b-xl border-x border-b border-[#eceef0] bg-white lg:block">
            <table class="w-full table-fixed text-left">
                <thead class="bg-[#f8f9fa] text-[10px] uppercase text-[#6b7280]">
                    <tr>
                        <th class="w-[30%] px-5 py-3 font-semibold">Nombre</th>
                        <th class="w-[16%] px-5 py-3 font-semibold">Clasificación</th>
                        <th class="w-[20%] px-5 py-3 font-semibold">Ubicación</th>
                        <th class="w-[15%] px-5 py-3 font-semibold">Modificado</th>
                        <th class="w-[11%] px-5 py-3 font-semibold">Tamaño</th>
                        <th class="w-[8%] px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eceef0]">
                    @foreach ($files as $file)
                        <tr class="text-[13px] transition hover:bg-[#fdfbf8]">
                            <td class="px-5 py-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-ui.icon :name="$file['icon']" :size="20" alt="" />
                                    <span class="truncate font-medium text-[#1f2937]">{{ $file['name'] }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'inline-flex rounded-md px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-brand/10 text-brand' => $file['tone'] === 'private',
                                    'bg-blue-50 text-blue-600' => $file['tone'] === 'collaborative',
                                    'bg-emerald-50 text-emerald-600' => $file['tone'] === 'public',
                                ])>{{ $file['visibility'] }}</span>
                            </td>
                            <td class="truncate px-5 py-3 text-[#6b7280]">{{ $file['location'] }}</td>
                            <td class="px-5 py-3 text-[#6b7280]">{{ $file['modified'] }}</td>
                            <td class="px-5 py-3 text-[#6b7280]">{{ $file['size'] }}</td>
                            <td class="px-5 py-3">
                                <button type="button" class="ml-auto flex size-8 items-center justify-center rounded-full hover:bg-[#f3f4f6]" aria-label="Acciones para {{ $file['name'] }}">
                                    <x-ui.icon name="more-horizontal" :size="16" alt="" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section aria-labelledby="recent-folders-title" class="hidden overflow-hidden rounded-xl border border-[#eceef0] bg-white lg:block">
            <div class="flex items-center justify-between border-b border-[#eceef0] p-5">
                <h3 id="recent-folders-title" class="text-[15px] font-bold">Carpetas Recientes</h3>
                <a href="#" class="text-[13px] font-semibold text-gold underline">Ver todas</a>
            </div>
            <div class="divide-y divide-[#eceef0]">
                @foreach ($folders as $folder)
                    <a href="#" class="flex items-center justify-between px-5 py-3 hover:bg-[#fdfbf8]">
                        <span class="flex items-center gap-3">
                            <x-ui.icon name="folder" :size="20" alt="" />
                            <span>
                                <span class="block text-sm font-semibold">{{ $folder['name'] }}</span>
                                <span class="block text-xs text-[#6b7280]">{{ $folder['location'] }}</span>
                            </span>
                        </span>
                        <span class="flex items-center gap-3 text-xs text-[#6b7280]">
                            {{ $folder['time'] }}
                            <x-ui.icon name="chevron-right" :size="16" alt="" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="activity-title">
            <h3 id="activity-title" class="mb-3 text-sm font-bold uppercase text-[#1f2937] lg:hidden">Actividad Reciente</h3>
            <div class="overflow-hidden rounded-xl border border-[#e5e7eb] bg-white">
                <div class="hidden border-b border-[#eceef0] p-5 lg:block">
                    <h3 class="text-[15px] font-bold">Actividad Reciente</h3>
                </div>
                <div class="divide-y divide-[#eceef0] px-4 py-2 lg:px-0 lg:py-0">
                    @foreach ($activities as $activity)
                        <div class="flex items-center gap-3 py-2.5 lg:px-5 lg:py-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-[#f3f4f6]">
                                <x-ui.icon :name="$activity['icon']" :size="14" alt="" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-[13px] font-medium">{{ $activity['text'] }}</span>
                                <span class="block text-[11px] text-[#6b7280]">{{ $activity['time'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
