@props([
    'label',
    'name',
    'type' => 'text',
    'error' => null,
])

<label class="block">
    <span class="mb-1.5 block text-[13px] font-semibold text-muted">{{ $label }}</span>
    <span class="relative block">
        <input
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $attributes->class([
                'h-[46px] w-full rounded-lg border bg-surface px-3.5 text-sm text-ink outline-none transition placeholder:text-muted focus:border-brand focus:ring-3 focus:ring-brand/10',
                'border-red-400' => $error,
                'border-line' => ! $error,
            ]) }}
        >
        {{ $suffix ?? '' }}
    </span>
    @if ($error)
        <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $error }}</span>
    @endif
</label>
