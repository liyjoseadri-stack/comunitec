@props(['mensaje'])

<div {{ $attributes->class(['estado-vacio']) }}>
    <p class="estado-vacio__mensaje">{{ $mensaje }}</p>
    @if (isset($acciones) && $acciones->isNotEmpty())
        <div class="estado-vacio__acciones">{{ $acciones }}</div>
    @endif
</div>
