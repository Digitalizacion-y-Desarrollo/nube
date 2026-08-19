@props([
    'code',
    'title',
    'message',
    'requiresSession' => true,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $code }} · {{ $title }} · {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/img/logo_nube.png') }}">
        <x-ui.theme-script />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-canvas px-6 py-12 font-sans text-ink antialiased">
        <div class="w-full max-w-md text-center">
            <x-ui.brand-logo width="72" height="48" class="mx-auto h-12 w-[72px] rounded-lg shadow-sm" />

            <p class="mt-6 text-sm font-extrabold uppercase tracking-[0.2em] text-gold-ink dark:text-gold">
                Error {{ $code }}
            </p>
            <h1 class="mt-2 text-2xl font-bold text-brand dark:text-white sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-6 text-muted">{{ $message }}</p>

            <div class="mt-8 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-center">
                @if (isset($secondaryAction))
                    {{ $secondaryAction }}
                @endif

                @if ($requiresSession)
                    {{--
                        El modo mantenimiento (503) intercepta la petición antes
                        de que la sesión arranque; comprobar la sesión ahí
                        lanzaría un error secundario. Por eso esta parte es
                        opcional y 503 la desactiva.
                    --}}
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex h-12 items-center justify-center rounded-[10px] bg-brand px-6 text-sm font-semibold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold">
                            Volver a mi nube
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center rounded-[10px] bg-brand px-6 text-sm font-semibold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold">
                            Ir a iniciar sesión
                        </a>
                    @endauth
                @endif
            </div>

            <p class="mt-10 text-[11px] text-muted">
                H. Ayuntamiento de Nezahualcóyotl · Nube Municipal
            </p>
        </div>
    </body>
</html>
