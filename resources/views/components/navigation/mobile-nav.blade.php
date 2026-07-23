@php
    $items = [
        ['label' => 'Inicio', 'icon' => 'home-mobile', 'active' => true],
        ['label' => 'Archivos', 'icon' => 'folder-nav-mobile'],
        ['label' => 'Depto', 'icon' => 'users-nav-mobile'],
        ['label' => 'Público', 'icon' => 'globe-mobile'],
        ['label' => 'Perfil', 'icon' => 'user'],
    ];
@endphp

<nav aria-label="Navegación inferior" class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 backdrop-blur-lg lg:hidden">
    <div class="mx-auto flex h-16 max-w-md items-center justify-between">
        @foreach ($items as $item)
            <a href="{{ route('dashboard') }}" @class([
                'flex h-14 w-16 flex-col items-center justify-center gap-1 text-[10px]',
                'font-semibold text-brand dark:text-white' => $item['active'] ?? false,
                'font-medium text-muted' => ! ($item['active'] ?? false),
            ])>
                <x-ui.icon :name="$item['icon']" :size="22" alt="" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
