@props([
    'variante' => 'principal',
    'href' => null,
    'tipo' => 'button',
    'compacto' => false,
    'icono' => null,
])

@php
    $clases = 'boton boton--'.$variante.($compacto ? ' boton--compacto' : '');
    $contenidoPlano = mb_strtolower(strip_tags((string) $slot));
    $iconoAutomatico = match (true) {
        str_contains($contenidoPlano, 'pdf'), str_contains($contenidoPlano, 'descargar') => 'descargar',
        str_contains($contenidoPlano, 'enviar') => 'enviar',
        str_contains($contenidoPlano, 'eliminar'), str_contains($contenidoPlano, 'desactivar') => 'eliminar',
        str_contains($contenidoPlano, 'editar') => 'editar',
        str_contains($contenidoPlano, 'guardar') => 'guardar',
        str_contains($contenidoPlano, 'regresar'), str_contains($contenidoPlano, 'volver'), str_contains($contenidoPlano, 'cancelar') => 'regresar',
        str_contains($contenidoPlano, 'filtrar'), str_contains($contenidoPlano, 'consultar') => 'buscar',
        str_contains($contenidoPlano, 'ver '), str_contains($contenidoPlano, 'historial') => 'ver',
        str_contains($contenidoPlano, 'crear'), str_contains($contenidoPlano, 'registrar'), str_contains($contenidoPlano, 'nueva'), str_contains($contenidoPlano, 'nuevo'), str_contains($contenidoPlano, 'agregar') => 'agregar',
        str_contains($contenidoPlano, 'aceptar') => 'aceptar',
        str_contains($contenidoPlano, 'rechazar') => 'cerrar-simple',
        default => null,
    };
    $iconoMostrado = $icono ?: $iconoAutomatico;
    $slotYaIncluyeIcono = str_contains((string) $slot, '<svg');
@endphp

@if ($href)
    <a {{ $attributes->class($clases)->merge(['href' => $href]) }}>
        @if ($iconoMostrado && ! $slotYaIncluyeIcono)
            <x-icono :nombre="$iconoMostrado" />
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->class($clases)->merge(['type' => $tipo]) }}>
        @if ($iconoMostrado && ! $slotYaIncluyeIcono)
            <x-icono :nombre="$iconoMostrado" />
        @endif
        {{ $slot }}
    </button>
@endif
