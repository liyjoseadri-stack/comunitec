@props(['titulo', 'descripcion' => null])

<header {{ $attributes->class('encabezado-pagina') }}>
    <div class="encabezado-pagina__texto">
        <h1>{{ $titulo }}</h1>
        @if ($descripcion)
            <p>{{ $descripcion }}</p>
        @endif
    </div>

    @if (isset($acciones))
        <div class="grupo-acciones">
            {{ $acciones }}
        </div>
    @endif
</header>
