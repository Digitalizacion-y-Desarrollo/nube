{{--
    Sin JavaScript a propósito: la CSP del proyecto (script-src 'self'
    'nonce-...', sin unsafe-inline) bloquea atributos onclick en línea. Un
    enlace normal a la página anterior evita el problema por completo.
--}}
<x-layouts.error
    code="419"
    title="Tu sesión de formulario expiró"
    message="Pasó demasiado tiempo desde que se cargó esta página. Vuelve atrás e inténtalo de nuevo."
>
    <x-slot:secondaryAction>
        <a href="{{ url()->previous('/') }}" class="inline-flex h-12 items-center justify-center rounded-[10px] border border-line px-6 text-sm font-semibold text-ink transition hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            Volver atrás
        </a>
    </x-slot:secondaryAction>
</x-layouts.error>
