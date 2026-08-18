@props([
    'title' => 'Inicio',
    'user' => [],
])

<header class="glass-shell sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line/70 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 lg:hidden">
        <button type="button" data-sidebar-open class="flex size-11 items-center justify-center rounded-full text-brand hover:bg-brand/5 dark:text-white" aria-label="Abrir navegación">
            <x-ui.icon name="menu" :size="24" alt="" />
        </button>
        <div class="flex items-center gap-2">
            <x-ui.brand-logo alt="" width="48" height="32" class="h-8 w-12 rounded-md" />
            <span class="hidden font-bold text-brand dark:text-white sm:block">Nube Municipal</span>
        </div>
    </div>

    <h1 class="hidden text-lg font-bold text-brand dark:text-white lg:block">{{ $title }}</h1>

    <div class="flex items-center gap-2 sm:gap-3 lg:gap-5">
        <button
            type="button"
            data-global-search-open
            class="group flex size-10 items-center justify-center rounded-full border border-line bg-surface text-muted hover:border-gold hover:bg-warm hover:text-brand focus-visible:outline-brand dark:hover:text-white lg:h-10 lg:w-80 lg:justify-start lg:gap-2 lg:rounded-full lg:px-4"
            aria-label="Abrir búsqueda global"
            aria-haspopup="dialog"
            aria-controls="global-search"
            aria-expanded="false"
        >
            <x-ui.icon name="search" :size="17" alt="" />
            <span class="hidden flex-1 text-left text-[13px] text-muted lg:block">Buscar archivos y carpetas...</span>
            <kbd class="hidden rounded-md border border-line bg-soft px-2 py-1 text-[10px] font-semibold text-muted lg:inline-flex">⌘ K</kbd>
        </button>
        <x-ui.theme-toggle />
        <div class="relative" data-help-menu>
            <button
                type="button"
                id="help"
                data-help-menu-trigger
                class="flex size-10 items-center justify-center rounded-full border border-line bg-surface text-muted transition hover:border-gold hover:bg-warm hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold dark:hover:text-white"
                aria-label="Ayuda"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="help-menu-panel"
                title="Ayuda"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M9.4 9.5a2.75 2.75 0 0 1 5.35.9c0 1.83-2.75 2.75-2.75 2.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="16.75" r="0.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>

            <div
                id="help-menu-panel"
                data-help-menu-panel
                role="menu"
                aria-labelledby="help"
                hidden
                class="absolute right-0 top-full z-40 mt-2 w-72 rounded-xl border border-line bg-surface p-2 shadow-xl"
            >
                <p class="px-2.5 pb-2 pt-1.5 text-[11px] font-bold uppercase tracking-wide text-muted">Recorridos guiados</p>
                <div data-help-menu-list class="space-y-0.5"></div>
                <p data-help-menu-empty hidden class="px-2.5 py-3 text-xs leading-5 text-muted">
                    Todavía no hay un recorrido guiado disponible para esta página.
                </p>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" title="Editar foto de perfil">
            <img src="{{ $user['avatar'] ?? asset('assets/figma/avatar-small.png') }}" alt="Foto de perfil de {{ $user['name'] ?? 'usuario' }}" width="36" height="36" class="size-9 rounded-full object-cover ring-1 ring-line">
        </a>
    </div>
</header>
