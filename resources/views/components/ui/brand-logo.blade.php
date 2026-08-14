@props([
    'alt' => 'Logo de Nube Nezahualcóyotl',
])

<img
    src="{{ asset('assets/img/logo_nube.png') }}"
    alt="{{ $alt }}"
    decoding="async"
    {{ $attributes->class('object-cover') }}
>
