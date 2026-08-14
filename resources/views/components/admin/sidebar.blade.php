@props([
    'user' => [],
])

@php
    $items = [
        ['label' => 'Resumen', 'icon' => 'home', 'route' => 'admin.dashboard'],
        ['label' => 'Archivos', 'icon' => 'file-text', 'route' => 'admin.files'],
        ['label' => 'Departamentos', 'icon' => 'building', 'route' => 'admin.departments'],
        ['label' => 'Usuarios', 'icon' => 'users', 'route' => 'admin.users'],
        ['label' => 'Papelera', 'icon' => 'trash', 'route' => 'admin.trash'],
        ['label' => 'Auditoría', 'icon' => 'shield', 'route' => 'admin.audit'],
        ['label' => 'Configuración', 'icon' => 'lock-keyhole', 'route' => 'admin.settings'],
    ];
@endphp

<aside class="sticky top-0 hidden h-screen w-[292px] shrink-0 flex-col border-r border-white/10 bg-brand-dark px-5 py-6 text-white lg:flex">
    <div>
        <div class="flex items-center gap-3 px-3 pb-6 pt-2">
            <x-ui.brand-logo width="48" height="32" class="h-8 w-12 rounded-md ring-1 ring-white/15" />
            <span>
                <span class="block text-sm font-bold">Nube Municipal</span>
                <span class="block text-[10px] font-semibold tracking-[0.16em] text-gold">ADMINISTRACIÓN</span>
            </span>
        </div>

        <div class="mb-5 h-px bg-white/10"></div>

        <nav aria-label="Navegación administrativa" class="space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition',
                        'bg-white font-semibold text-brand shadow-sm' => request()->routeIs($item['route']),
                        'font-medium text-white/75 hover:bg-white/10 hover:text-white' => ! request()->routeIs($item['route']),
                    ])
                    @if (request()->routeIs($item['route'])) aria-current="page" @endif
                >
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="mt-auto space-y-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg border border-white/15 px-4 py-3 text-sm font-semibold text-white hover:bg-white/10">
            <x-ui.icon name="chevron-right" :size="18" alt="" class="rotate-180" />
            <span>Volver a mi nube</span>
        </a>

        <div class="flex items-center gap-3 border-t border-white/10 pt-4">
            <a href="{{ route('profile.edit') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg p-1 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold" title="Editar foto de perfil">
                <img src="{{ $user['avatar'] ?? asset('assets/figma/avatar.png') }}" alt="Foto de perfil de {{ $user['name'] ?? 'superusuario' }}" width="40" height="40" class="size-10 shrink-0 rounded-full object-cover ring-2 ring-white/10">
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ $user['name'] ?? 'Superusuario' }}</span>
                    <span class="block truncate text-xs text-white/55">{{ $user['department'] ?? 'Sin departamento' }}</span>
                </span>
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-gold hover:bg-white/10" title="Cerrar sesión">
                    Salir
                </button>
            </form>
        </div>
    </div>
</aside>

<div id="mobile-sidebar" class="fixed inset-0 z-50 hidden lg:hidden" aria-hidden="true">
    <button type="button" data-sidebar-close class="absolute inset-0 bg-[#12070d]/65 backdrop-blur-sm" aria-label="Cerrar navegación"></button>
    <aside class="relative flex h-full w-[min(86vw,340px)] flex-col bg-brand-dark p-5 text-white shadow-2xl">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-ui.brand-logo width="48" height="32" class="h-8 w-12 rounded-md ring-1 ring-white/15" />
                <span>
                    <span class="block text-sm font-bold">Nube Municipal</span>
                    <span class="block text-[9px] font-semibold tracking-[0.14em] text-gold">ADMINISTRACIÓN</span>
                </span>
            </div>
            <button type="button" data-sidebar-close class="rounded-full p-2 text-xl text-white/70 hover:bg-white/10" aria-label="Cerrar">×</button>
        </div>

        <nav aria-label="Navegación administrativa móvil" class="flex-1 space-y-1 overflow-y-auto">
            @foreach ($items as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 text-sm',
                        'bg-white font-semibold text-brand' => request()->routeIs($item['route']),
                        'font-medium text-white/75' => ! request()->routeIs($item['route']),
                    ])
                    @if (request()->routeIs($item['route'])) aria-current="page" @endif
                >
                    <x-ui.icon :name="$item['icon']" :size="20" alt="" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="space-y-3 border-t border-white/10 pt-4">
            <a href="{{ route('dashboard') }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/20 px-4 py-3 text-sm font-semibold text-white">
                <x-ui.icon name="chevron-right" :size="18" alt="" class="rotate-180" />
                Volver a mi nube
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-white px-4 py-3 text-sm font-semibold text-brand">Cerrar sesión</button>
            </form>
        </div>
    </aside>
</div>
