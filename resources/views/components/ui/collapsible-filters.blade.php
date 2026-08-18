@props([
    'label' => 'Filtros',
])

{{--
    Evita el "muro de filtros" en móvil: en vez de mostrar todos los campos
    siempre expandidos antes del contenido, se usa <details> nativo (sin
    dependencias, accesible por defecto) colapsado de inicio. app.js lo abre
    automáticamente en escritorio (>= 1024px, el mismo punto de quiebre `lg:`
    que ya usa el resto de la interfaz) para no cambiar el comportamiento
    actual ahí; en móvil queda colapsado hasta que el usuario lo pide.
--}}
<div class="mb-5 overflow-hidden rounded-xl border border-line bg-surface">
    <details data-collapsible-filters>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3.5 text-sm font-bold text-ink [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <x-ui.icon name="search" :size="16" alt="" />
                {{ $label }}
            </span>
            <x-ui.icon
                name="chevron-right"
                :size="16"
                alt=""
                data-collapsible-chevron
                class="shrink-0 transition-transform duration-200"
            />
        </summary>
        <div class="border-t border-line p-4">
            {{ $slot }}
        </div>
    </details>
</div>
