@props([
    'items' => [],
])

<nav aria-label="Migas de navegación" class="flex items-center gap-2 text-sm text-[#6b7280]">
    @foreach ($items as $item)
        @if (! $loop->first)
            <x-ui.icon name="chevron-right" :size="14" aria-hidden="true" />
        @endif
        @if (isset($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-brand">{{ $item['label'] }}</a>
        @else
            <span @class(['font-semibold text-brand' => $loop->last])>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
