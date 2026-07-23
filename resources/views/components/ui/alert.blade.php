@props([
    'tone' => 'error',
])

@php
    $tones = [
        'error' => 'border-red-200 bg-red-50 text-red-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'info' => 'border-blue-200 bg-blue-50 text-blue-700',
    ];
@endphp

<div
    role="alert"
    {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-3 py-2.5 text-xs font-medium', $tones[$tone] ?? $tones['info']]) }}
>
    <span aria-hidden="true" class="mt-0.5 size-1.5 shrink-0 rounded-full bg-current"></span>
    <span>{{ $slot }}</span>
</div>
