@props([
    'permissions' => [],
])

@php
    $items = [
        ['label' => 'Inicio', 'icon' => 'home-mobile', 'route' => 'dashboard', 'permission' => null],
        ['label' => 'Archivos', 'icon' => 'folder-nav-mobile', 'route' => 'folders.mine', 'pattern' => 'folders.mine*', 'permission' => 'nube_mis_archivos_ver'],
        ['label' => 'Depto', 'icon' => 'users-nav-mobile', 'route' => 'folders.department', 'pattern' => 'folders.department*', 'permission' => 'nube_departamento_ver'],
        ['label' => 'Área', 'icon' => 'users-nav-mobile', 'route' => 'folders.area', 'pattern' => 'folders.area*', 'permission' => 'nube_area_ver', 'requires_area' => true],
        ['label' => 'Públicos', 'icon' => 'globe-mobile', 'route' => 'folders.public', 'pattern' => 'folders.public*', 'permission' => 'nube_publicos_ver'],
        ['label' => 'Papelera', 'icon' => 'trash', 'route' => 'folders.trash', 'permission' => 'nube_papelera_ver'],
    ];

    $isAdministrator = in_array('nube_administracion_administrar', $permissions, true);
    $adminViews = [
        'admin.dashboard' => 'nube_administracion_resumen_ver',
        'admin.files' => 'nube_administracion_archivos_ver',
        'admin.departments' => 'nube_administracion_departamentos_ver',
        'admin.users' => 'nube_administracion_usuarios_ver',
        'admin.trash' => 'nube_administracion_papelera_ver',
        'admin.audit' => 'nube_administracion_auditoria_ver',
        'admin.settings' => 'nube_administracion_configuracion_ver',
    ];
    $adminEntryRoute = collect($adminViews)->search(
        fn (string $permission): bool => in_array($permission, $permissions, true),
    );
    $items = array_filter(
        $items,
        fn (array $item): bool => (! ($item['requires_area'] ?? false)
                || filled(session('access.department.children.0.id')))
            && ($item['permission'] === null
                || $isAdministrator
                || in_array($item['permission'], $permissions, true)),
    );

    // El panel administrativo no vive en el menú lateral móvil por permiso
    // funcional, sino por el rol `superuser` (ver AGENT.md); antes sólo se
    // podía llegar ahí abriendo el menú hamburguesa, a diferencia del
    // escritorio, donde es un enlace siempre visible en la barra lateral.
    if (auth()->user()?->hasRole('superuser') && $adminEntryRoute !== false) {
        $items[] = [
            'label' => 'Admin',
            'icon' => 'shield',
            'route' => $adminEntryRoute,
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
                <x-ui.icon :name="$item['icon']" :size="22" alt="" :class="$item['route'] === 'dashboard' ? 'brightness-0' : null" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
