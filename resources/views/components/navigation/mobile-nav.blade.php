@props([
    'permissions' => [],
])

@php
    $items = [
        ['label' => 'Inicio', 'icon' => 'home-mobile', 'route' => 'dashboard', 'permission' => null],
        ['label' => 'Archivos', 'icon' => 'folder-nav-mobile', 'route' => 'folders.mine', 'pattern' => 'folders.mine*', 'permission' => 'nube_mis_archivos_ver'],
        ['label' => 'Depto', 'icon' => 'users-nav-mobile', 'route' => 'folders.department', 'pattern' => 'folders.department*', 'permission' => 'nube_departamento_ver'],
        ['label' => 'Públicos', 'icon' => 'globe-mobile', 'route' => 'folders.public', 'pattern' => 'folders.public*', 'permission' => 'nube_publicos_ver'],
        ['label' => 'Papelera', 'icon' => 'trash', 'route' => 'folders.trash', 'permission' => 'nube_papelera_ver'],
    ];

    $isAdministrator = in_array('nube_administracion_administrar', $permissions, true);
    $items = array_filter(
        $items,
        fn (array $item): bool => $item['permission'] === null
            || $isAdministrator
            || in_array($item['permission'], $permissions, true),
    );

    // El panel administrativo no vive en el menú lateral móvil por permiso
    // funcional, sino por el rol `superuser` (ver AGENT.md); antes sólo se
    // podía llegar ahí abriendo el menú hamburguesa, a diferencia del
    // escritorio, donde es un enlace siempre visible en la barra lateral.
    if (auth()->user()?->hasRole('superuser')) {
        $items[] = [
            'label' => 'Admin',
            'icon' => 'shield',
            'route' => 'admin.dashboard',
            'pattern' => 'admin.*',
            'permission' => null,
        ];
    }
@endphp

<nav aria-label="Navegación inferior" class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 backdrop-blur-lg lg:hidden">
    <div class="mx-auto flex h-16 max-w-md items-center justify-between">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" @class([
                'flex h-14 w-16 flex-col items-center justify-center gap-1 text-[10px]',
                'font-semibold text-brand dark:text-white' => request()->routeIs($item['pattern'] ?? $item['route']),
                'font-medium text-muted' => ! request()->routeIs($item['pattern'] ?? $item['route']),
            ]) @if (request()->routeIs($item['pattern'] ?? $item['route'])) aria-current="page" @endif>
                <x-ui.icon :name="$item['icon']" :size="22" alt="" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
