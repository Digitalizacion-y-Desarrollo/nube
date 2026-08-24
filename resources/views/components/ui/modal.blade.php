@props([
    'id',
    'title',
    'panelClass' => 'max-w-lg',
])

<div
    id="{{ $id }}"
    data-modal
    {{ $attributes->class('fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-[#1f1f24]/45 p-2 pt-[max(0.5rem,env(safe-area-inset-top))] pb-[max(0.5rem,env(safe-area-inset-bottom))] backdrop-blur-sm sm:items-center sm:p-4') }}
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    aria-hidden="true"
>
    <div data-modal-panel tabindex="-1" class="my-auto flex max-h-[calc(100dvh-1rem)] w-full {{ $panelClass }} flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl outline-none sm:max-h-[calc(100dvh-2rem)]">
        <div class="flex shrink-0 items-center justify-between gap-4 px-5 pb-4 pt-5 sm:px-6 sm:pb-5 sm:pt-6">
            <h2 id="{{ $id }}-title" class="text-lg font-bold text-ink">{{ $title }}</h2>
            <button type="button" data-modal-close="{{ $id }}" class="rounded-full p-2 text-muted hover:bg-soft" aria-label="Cerrar">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 sm:px-6 sm:pb-6">
            {{ $slot }}
        </div>
    </div>
</div>
