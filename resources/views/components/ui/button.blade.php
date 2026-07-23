@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'border-brand bg-brand text-white hover:bg-[#4f122b] focus-visible:outline-brand',
        'secondary' => 'border-line bg-surface text-ink hover:border-gold hover:bg-warm focus-visible:outline-gold',
        'outline' => 'border-gold bg-warm text-brand hover:bg-soft focus-visible:outline-gold dark:text-white',
        'danger' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus-visible:outline-red-600',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex min-h-11 items-center justify-center gap-2 rounded-[10px] border px-5 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
    ]) }}
>
    {{ $slot }}
</button>
