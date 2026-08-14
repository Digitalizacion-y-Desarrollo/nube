@props([
    'title' => 'Resumen',
    'user' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Panel de administración de Nube Municipal.">
        <title>{{ $title }} · Administración · {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/img/logo_nube.png') }}">
        <x-ui.theme-script />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <div class="min-h-screen lg:flex">
            <x-admin.sidebar :user="$user" />

            <div class="min-w-0 flex-1">
                <header class="glass-shell sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line/70 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" data-sidebar-open class="flex size-11 items-center justify-center rounded-full text-brand hover:bg-brand/5 dark:text-white lg:hidden" aria-label="Abrir navegación administrativa">
                            <x-ui.icon name="menu" :size="24" alt="" />
                        </button>
                        <span class="hidden rounded-full bg-brand/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-brand dark:bg-white/10 dark:text-white sm:inline-flex">
                            Superusuario
                        </span>
                        <h1 class="text-base font-bold text-brand dark:text-white sm:text-lg">{{ $title }}</h1>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('dashboard') }}" class="hidden items-center gap-2 rounded-full border border-line bg-surface px-4 py-2 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white sm:inline-flex">
                            <x-ui.icon name="chevron-right" :size="15" alt="" class="rotate-180" />
                            Mi nube
                        </a>
                        <x-ui.theme-toggle />
                        <a href="{{ route('profile.edit') }}" class="rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold" title="Editar foto de perfil">
                            <img src="{{ $user['avatar'] ?? asset('assets/figma/avatar-small.png') }}" alt="Foto de perfil de {{ $user['name'] ?? 'superusuario' }}" width="36" height="36" class="size-9 rounded-full object-cover ring-1 ring-line">
                        </a>
                    </div>
                </header>

                <main class="px-5 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
