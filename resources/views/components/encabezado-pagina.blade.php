@props(['titulo', 'descripcion' => null])

<header {{ $attributes->class('encabezado-pagina') }}>
    @if ($descripcion)
        <div class="encabezado-pagina__texto">
            <p>{{ $descripcion }}</p>
        </div>
    @endif

    @if (isset($acciones))
        <div class="grupo-acciones">
            {{ $acciones }}
        </div>
    @endif
</header>
