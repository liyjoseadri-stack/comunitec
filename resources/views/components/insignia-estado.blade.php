@props(['estado', 'etiqueta' => null])

@php
    $estadoNormalizado = strtolower((string) $estado);
    $clase = match ($estadoNormalizado) {
        'aceptada', 'venta', 'activo', 'activa', 'disponible', 'entregada', 'cerrada' => 'exito',
        'pendiente', 'reservada', 'borrador' => 'advertencia',
        'rechazada', 'cancelada', 'vencida', 'inactivo', 'inactiva' => 'peligro',
        default => 'neutra',
    };
@endphp

<span {{ $attributes->class("insignia insignia--{$clase}") }}>
    {{ $etiqueta ?? ucfirst($estadoNormalizado) }}
</span>
