@props([
    'label',
    'name',
    'type' => 'text',
    'error' => null,
])

<label class="block">
    <span class="mb-1.5 block text-[13px] font-semibold text-[#4b5563]">{{ $label }}</span>
    <span class="relative block">
        <input
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $attributes->class([
                'h-[46px] w-full rounded-lg border bg-white px-3.5 text-sm text-[#1f2937] outline-none transition placeholder:text-[#9ca3af] focus:border-brand focus:ring-3 focus:ring-brand/10',
                'border-red-400' => $error,
                'border-[#e5e7eb]' => ! $error,
            ]) }}
        >
        {{ $suffix ?? '' }}
    </span>
    @if ($error)
        <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $error }}</span>
    @endif
</label>
