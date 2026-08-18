@props([
    'label' => 'Más información',
])

{{--
    Ayuda puntual junto a un control concreto, no un recorrido de página.
    Se implementa aparte de los tours de driver.js (ver app.js `buildHelpMenu`)
    porque varias instancias viven dentro de modales con su propio fondo
    (`x-ui.modal`); el overlay de pantalla completa de driver.js chocaría con
    ese fondo. El popover se ancla a la izquierda del botón, no centrado,
    porque el panel del modal usa `overflow-y-auto`, que por especificación
    CSS también recorta el desbordamiento horizontal.
--}}
<span class="relative inline-flex" data-help-tip>
    <button
        type="button"
        data-help-tip-trigger
        class="inline-flex size-[18px] shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-[11px] font-bold leading-none text-blue-700 transition hover:border-blue-400 hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20"
        aria-label="{{ $label }}"
        aria-expanded="false"
    >
        ?
    </button>
    <span
        data-help-tip-panel
        role="tooltip"
        hidden
        class="absolute left-0 top-full z-20 mt-2 w-60 rounded-lg border border-line bg-surface p-3 text-xs leading-5 text-muted shadow-xl"
    >
        {{ $slot }}
    </span>
</span>
