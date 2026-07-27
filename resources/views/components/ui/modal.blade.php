@props([
    'id',
    'title',
])

<div
    id="{{ $id }}"
    data-modal
    {{ $attributes->class('fixed inset-0 z-50 hidden items-center justify-center bg-[#1f1f24]/45 p-4 backdrop-blur-sm') }}
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-bold text-ink">{{ $title }}</h2>
            <button type="button" data-modal-close="{{ $id }}" class="rounded-full p-2 text-muted hover:bg-soft" aria-label="Cerrar">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
