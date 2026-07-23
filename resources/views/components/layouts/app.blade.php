@props([
    'title' => 'Inicio',
    'user' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Panel de Nube Empresarial.">
        <title>{{ $title }} · {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-[#1f2937] antialiased">
        <div class="min-h-screen lg:flex">
            <x-navigation.sidebar :user="$user" />

            <div class="min-w-0 flex-1">
                <x-navigation.header :title="$title" :user="$user" />
                <main class="px-5 py-6 pb-28 sm:px-6 lg:px-8 lg:py-7 lg:pb-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-navigation.mobile-nav />
        <x-ui.modal id="upload-modal" title="Subir archivo">
            <p class="text-sm leading-6 text-[#6b7280]">El flujo de carga se conectará durante el Épico 5. La interfaz base ya está preparada.</p>
        </x-ui.modal>
        <x-ui.modal id="folder-modal" title="Nueva carpeta">
            <p class="text-sm leading-6 text-[#6b7280]">La creación de carpetas se conectará durante el Épico 4.</p>
        </x-ui.modal>
    </body>
</html>
