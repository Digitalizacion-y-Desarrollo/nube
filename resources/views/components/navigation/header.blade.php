@props([
    'title' => 'Inicio',
    'user' => [],
])

<header class="glass-shell sticky top-0 z-30 flex h-16 items-center justify-between border-b border-[#e5e7eb]/70 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 lg:hidden">
        <button type="button" data-sidebar-open class="flex size-11 items-center justify-center rounded-full text-brand hover:bg-brand/5" aria-label="Abrir navegación">
            <x-ui.icon name="menu" :size="24" alt="" />
        </button>
        <div class="flex items-center gap-2">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand text-sm font-extrabold text-white">NE</span>
            <span class="hidden font-bold text-brand sm:block">Nube Empresarial</span>
        </div>
    </div>

    <h1 class="hidden text-lg font-bold text-brand lg:block">{{ $title }}</h1>

    <div class="flex items-center gap-2 sm:gap-3 lg:gap-5">
        <label class="hidden w-80 items-center gap-2 rounded-full border border-[#eceef0] bg-white px-4 py-2 lg:flex">
            <x-ui.icon name="search" :size="16" alt="" />
            <span class="sr-only">Buscar</span>
            <input type="search" placeholder="Buscar archivos, carpetas o departamentos..." class="min-w-0 flex-1 text-[13px] text-[#1f2937] outline-none placeholder:text-[#6b7280]">
        </label>
        <button type="button" class="flex size-10 items-center justify-center rounded-full border border-[#eceef0] bg-white hover:border-gold" aria-label="Notificaciones">
            <x-ui.icon name="bell" :size="20" alt="" />
        </button>
        <button type="button" aria-label="Abrir perfil">
            <img src="{{ asset('assets/figma/avatar-small.png') }}" alt="" width="36" height="36" class="size-9 rounded-full object-cover">
        </button>
    </div>
</header>
