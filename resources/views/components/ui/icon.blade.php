@props([
    'name',
    'size' => 20,
    'alt' => '',
])

<img
    src="{{ asset("assets/figma/{$name}.svg") }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    {{ $attributes->class('shrink-0 object-contain') }}
>
