@props([
    'user' => [],
    'permissions' => [],
])

@php
    $items = [
        ['label' => 'Inicio', 'icon' => 'home', 'route' => 'dashboard', 'permission' => null],
        ['label' => 'Mis Archivos', 'icon' => 'folder-open', 'route' => 'folders.mine', 'pattern' => 'folders.mine*', 'permission' => 'nube_mis_archivos_ver'],
        ['label' => 'Mi departamento', 'icon' => 'building', 'route' => 'folders.department', 'pattern' => 'folders.department*', 'permission' => 'nube_departamento_ver'],
        ['label' => 'Públicos', 'icon' => 'globe', 'route' => 'folders.public', 'pattern' => 'folders.public*', 'permission' => 'nube_publicos_ver'],
        ['label' => 'Papelera', 'icon' => 'trash', 'route' => 'folders.trash', 'permission' => 'nube_papelera_ver'],
    ];

    $isAdministrator = in_array('nube_administracion_administrar', $permissions, true);
    $items = array_filter(
        $items,
        fn (array $item): bool => $item['permission'] === null
            || $isAdministrator
            || in_array($item['permission'], $permissions, true),
    );
@endphp

<aside class="glass-shell sticky top-0 hidden h-screen w-[280px] shrink-0 flex-col justify-between border-r border-line/70 px-5 py-6 lg:flex">
    <div>
        <div class="flex flex-col items-center gap-1.5 px-5 pb-6 pt-3">
            <img src="{{ asset('assets/figma/logo-nezahualcoyotl.png') }}" alt="Escudo de Nezahualcóyotl" width="76" height="60" class="h-[60px] w-[76px] object-cover">
            <p class="text-base font-bold text-ink">Nube Municipal</p>
            <p class="text-[10px] tracking-[0.15em] text-muted">PORTAL DIGITAL</p>
        </div>

        <div class="mb-5 h-px bg-line"></div>

        <nav aria-label="Navegación principal" class="space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition',
                        'bg-brand font-semibold text-white shadow-sm' => request()->routeIs($item['pattern'] ?? $item['route']),
                        'font-medium text-ink hover:bg-brand/10 hover:text-brand dark:hover:text-white' => ! request()->routeIs($item['pattern'] ?? $item['route']),
                    ])
                    @if (request()->routeIs($item['pattern'] ?? $item['route'])) aria-current="page" @endif
                >
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div>
        <div class="flex w-full items-center gap-3 rounded-xl p-1 text-left">
            <img src="{{ $user['avatar'] ?? asset('assets/figma/avatar.png') }}" alt="" width="40" height="40" class="size-10 rounded-full object-cover">
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold">{{ $user['name'] ?? 'Carlos Martínez' }}</span>
                <span class="block truncate text-xs text-muted">{{ $user['department'] ?? 'Recursos Humanos' }}</span>
            </span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand hover:bg-brand/10 dark:text-white" title="Cerrar sesión">
                    Salir
                </button>
            </form>
        </div>
    </div>
</aside>

<div id="mobile-sidebar" class="fixed inset-0 z-50 hidden lg:hidden" aria-hidden="true">
    <button type="button" data-sidebar-close class="absolute inset-0 bg-[#1f1f24]/45 backdrop-blur-sm" aria-label="Cerrar navegación"></button>
    <aside class="relative flex h-full w-[min(82vw,320px)] flex-col bg-surface p-5 shadow-2xl">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-brand text-sm font-extrabold text-white">NE</span>
                <span class="font-bold text-brand dark:text-white">Nube Municipal</span>
            </div>
            <button type="button" data-sidebar-close class="rounded-full p-2 text-xl text-muted" aria-label="Cerrar">×</button>
        </div>
        <nav aria-label="Navegación móvil" class="flex-1 space-y-1">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'flex items-center gap-3 rounded-lg px-4 py-3 text-sm',
                    'bg-brand font-semibold text-white' => request()->routeIs($item['pattern'] ?? $item['route']),
                    'font-medium text-ink' => ! request()->routeIs($item['pattern'] ?? $item['route']),
                ]) @if (request()->routeIs($item['pattern'] ?? $item['route'])) aria-current="page" @endif>
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <form action="{{ route('logout') }}" method="POST" class="border-t border-line pt-4">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-brand px-4 py-3 text-sm font-semibold text-brand dark:text-white">Cerrar sesión</button>
        </form>
    </aside>
</div>
