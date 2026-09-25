@props(['estado', 'etiqueta' => null])

@php
    $estadoNormalizado = strtolower((string) $estado);
    $clase = match ($estadoNormalizado) {
        'aceptada', 'activo', 'disponible', 'entregada', 'cerrada' => 'exito',
        'pendiente', 'reservada', 'borrador' => 'advertencia',
        'rechazada', 'cancelada', 'vencida', 'inactivo' => 'peligro',
        default => 'neutra',
    };
@endphp

<span {{ $attributes->class("insignia insignia--{$clase}") }}>
    {{ $etiqueta ?? ucfirst($estadoNormalizado) }}
</span>
