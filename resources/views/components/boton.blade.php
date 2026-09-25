@props([
    'variante' => 'principal',
    'href' => null,
    'tipo' => 'button',
    'compacto' => false,
])

@php
    $clases = 'boton boton--'.$variante.($compacto ? ' boton--compacto' : '');
@endphp

@if ($href)
    <a {{ $attributes->class($clases)->merge(['href' => $href]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->class($clases)->merge(['type' => $tipo]) }}>
        {{ $slot }}
    </button>
@endif
