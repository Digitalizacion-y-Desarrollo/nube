{{--
    Reemplaza el tema Tailwind por defecto de Laravel para que la paginación
    (usada en todas las listas del explorador y del panel administrativo) siga
    la misma paleta de marca que el resto de la interfaz, en vez de los grises
    genéricos del paquete. La estructura y la lógica son las del paquete
    original; sólo cambian las clases.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex items-center justify-between gap-2 sm:hidden">

            @if ($paginator->onFirstPage())
                <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-muted">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:border-gold hover:bg-warm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:border-gold hover:bg-warm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-muted">
                    {!! __('pagination.next') !!}
                </span>
            @endif

        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-2">

            <div>
                <p class="text-sm leading-5 text-muted">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex overflow-hidden rounded-lg border border-line rtl:flex-row-reverse">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center border-r border-line bg-surface px-2.5 py-2 text-muted" aria-hidden="true">
                                <x-ui.icon name="chevron-right" :size="16" alt="" class="rotate-180" />
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center border-r border-line bg-surface px-2.5 py-2 text-ink transition hover:bg-warm focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand" aria-label="{{ __('pagination.previous') }}">
                            <x-ui.icon name="chevron-right" :size="16" alt="" class="rotate-180" />
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center border-r border-line bg-surface px-4 py-2 text-sm font-medium text-muted">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center border-r border-line bg-brand px-4 py-2 text-sm font-semibold text-white">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center border-r border-line bg-surface px-4 py-2 text-sm font-medium text-ink transition hover:bg-warm focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center bg-surface px-2.5 py-2 text-ink transition hover:bg-warm focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand" aria-label="{{ __('pagination.next') }}">
                            <x-ui.icon name="chevron-right" :size="16" alt="" />
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center bg-surface px-2.5 py-2 text-muted" aria-hidden="true">
                                <x-ui.icon name="chevron-right" :size="16" alt="" />
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
