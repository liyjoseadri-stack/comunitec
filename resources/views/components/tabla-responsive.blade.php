@props(['etiqueta', 'adaptable' => false, 'vacia' => false])

@php
    $requiereDesplazamiento = ! $adaptable && ! $vacia;
    $instruccionId = $requiereDesplazamiento ? 'tabla-instruccion-'.\Illuminate\Support\Str::uuid() : null;
@endphp

@if ($requiereDesplazamiento)
    <p id="{{ $instruccionId }}" class="tabla-responsive__instruccion">
        Desplaza la tabla horizontalmente para consultar todas las columnas.
        Con teclado, enfoca la tabla y usa las flechas izquierda y derecha.
    </p>
@endif
<div
    {{ $attributes->class(['tabla-responsive', 'contenedor-tabla', 'tabla-responsive--tecnica' => $requiereDesplazamiento, 'tabla-responsive--vacia' => $vacia]) }}
    role="region"
    aria-label="{{ $etiqueta }}"
    tabindex="0"
    @if ($requiereDesplazamiento) aria-describedby="{{ $instruccionId }}" @endif
>
    {{ $slot }}
</div>
