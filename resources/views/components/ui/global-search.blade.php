<div
    id="global-search"
    data-global-search
    data-search-url="{{ route('search') }}"
    data-folder-icon="{{ asset('assets/figma/folder.svg') }}"
    data-file-icon="{{ asset('assets/figma/file-text.svg') }}"
    class="spotlight-overlay fixed inset-0 z-[70] hidden items-start justify-center px-4 pb-4 pt-[max(5rem,14vh)] sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="global-search-title"
    aria-hidden="true"
>
    <section data-global-search-panel class="spotlight-panel w-full max-w-2xl overflow-hidden rounded-[24px] border border-white/50 shadow-[0_28px_90px_rgba(31,15,23,0.32)] outline-none dark:border-white/10" tabindex="-1">
        <h2 id="global-search-title" class="sr-only">Buscar en Nube Municipal</h2>

        <div class="flex items-center gap-3 border-b border-white/50 px-4 py-3.5 dark:border-white/10 sm:px-5 sm:py-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand dark:bg-white/10 dark:text-white">
                <x-ui.icon name="search" :size="21" alt="" />
            </span>
            <label for="global-search-input" class="sr-only">Buscar archivos y carpetas</label>
            <input
                id="global-search-input"
                data-global-search-input
                type="search"
                maxlength="100"
                autocomplete="off"
                spellcheck="false"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="global-search-results"
                aria-expanded="false"
                aria-describedby="global-search-status"
                placeholder="Buscar archivos y carpetas..."
                class="min-w-0 flex-1 bg-transparent text-base font-medium text-ink outline-none placeholder:font-normal placeholder:text-muted sm:text-lg"
            >
            <button type="button" data-global-search-close class="rounded-lg border border-white/60 bg-white/45 px-2 py-1 text-[11px] font-semibold text-muted shadow-sm hover:bg-white/75 focus-visible:outline-brand dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10" aria-label="Cerrar buscador">
                ESC
            </button>
        </div>

        <div class="max-h-[min(60vh,520px)] min-h-44 overflow-y-auto p-2 sm:p-3">
            <p id="global-search-status" data-global-search-status class="flex min-h-40 items-center justify-center px-5 text-center text-sm leading-6 text-muted" aria-live="polite">
                Escribe al menos 2 caracteres para buscar en todo tu contenido disponible.
            </p>
            <ul id="global-search-results" data-global-search-results class="hidden space-y-1" role="listbox" aria-label="Resultados de búsqueda"></ul>
        </div>

        <footer class="hidden items-center justify-between border-t border-white/50 px-5 py-3 text-[11px] text-muted dark:border-white/10 sm:flex">
            <span>Los resultados respetan tus permisos y departamento.</span>
            <span class="flex items-center gap-3">
                <span><kbd class="spotlight-key">↑</kbd><kbd class="spotlight-key">↓</kbd> navegar</span>
                <span><kbd class="spotlight-key">↵</kbd> abrir</span>
            </span>
        </footer>
    </section>
</div>
