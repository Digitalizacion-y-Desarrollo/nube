@props([
    'user' => [],
])

@php
    $items = [
        ['label' => 'Inicio', 'icon' => 'home', 'active' => true],
        ['label' => 'Mis Archivos', 'icon' => 'folder-open'],
        ['label' => 'Compartidos', 'icon' => 'users'],
        ['label' => 'Departamento', 'icon' => 'building'],
        ['label' => 'Público Interno', 'icon' => 'globe'],
        ['label' => 'Recientes', 'icon' => 'clock'],
        ['label' => 'Papelera', 'icon' => 'trash'],
    ];
@endphp

<aside class="glass-shell sticky top-0 hidden h-screen w-[280px] shrink-0 flex-col justify-between border-r border-[#e5e7eb]/70 px-5 py-6 lg:flex">
    <div>
        <div class="flex flex-col items-center gap-1.5 px-5 pb-6 pt-3">
            <img src="{{ asset('assets/figma/logo-nezahualcoyotl.png') }}" alt="Escudo de Nezahualcóyotl" width="76" height="60" class="h-[60px] w-[76px] object-cover">
            <p class="text-base font-bold text-[#1f1f24]">Nube Empresarial</p>
            <p class="text-[10px] tracking-[0.15em] text-[#6b737d]">PORTAL DIGITAL</p>
        </div>

        <div class="mb-5 h-px bg-[#e5e5e8]"></div>

        <nav aria-label="Navegación principal" class="space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ route('dashboard') }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition',
                        'bg-brand font-semibold text-white shadow-sm' => $item['active'] ?? false,
                        'font-medium text-[#1f2937] hover:bg-brand/5 hover:text-brand' => ! ($item['active'] ?? false),
                    ])
                >
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="space-y-5">
        <section aria-label="Uso de almacenamiento" class="rounded-xl bg-black/[0.02] p-4">
            <div class="mb-2 flex justify-between text-xs">
                <span class="font-semibold">Almacenamiento</span>
                <span class="font-medium text-[#6b7280]">24.8%</span>
            </div>
            <div class="mb-2 h-1.5 overflow-hidden rounded-full bg-[#e5e7eb]">
                <div class="h-full w-[24.8%] rounded-full bg-brand"></div>
            </div>
            <p class="text-[11px] text-[#6b7280]">12.4 GB / 50 GB utilizados</p>
        </section>

        <button type="button" class="flex w-full items-center gap-3 rounded-xl p-1 text-left transition hover:bg-black/[0.03]">
            <img src="{{ $user['avatar'] ?? asset('assets/figma/avatar.png') }}" alt="" width="40" height="40" class="size-10 rounded-full object-cover">
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold">{{ $user['name'] ?? 'Carlos Martínez' }}</span>
                <span class="block truncate text-xs text-[#6b7280]">{{ $user['department'] ?? 'Recursos Humanos' }}</span>
            </span>
        </button>
    </div>
</aside>

<div id="mobile-sidebar" class="fixed inset-0 z-50 hidden lg:hidden" aria-hidden="true">
    <button type="button" data-sidebar-close class="absolute inset-0 bg-[#1f1f24]/45 backdrop-blur-sm" aria-label="Cerrar navegación"></button>
    <aside class="relative flex h-full w-[min(82vw,320px)] flex-col bg-white p-5 shadow-2xl">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-brand text-sm font-extrabold text-white">NE</span>
                <span class="font-bold text-brand">Nube Empresarial</span>
            </div>
            <button type="button" data-sidebar-close class="rounded-full p-2 text-xl text-[#6b7280]" aria-label="Cerrar">×</button>
        </div>
        <nav aria-label="Navegación móvil" class="space-y-1">
            @foreach ($items as $item)
                <a href="{{ route('dashboard') }}" @class([
                    'flex items-center gap-3 rounded-lg px-4 py-3 text-sm',
                    'bg-brand font-semibold text-white' => $item['active'] ?? false,
                    'font-medium text-[#1f2937]' => ! ($item['active'] ?? false),
                ])>
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>
</div>
