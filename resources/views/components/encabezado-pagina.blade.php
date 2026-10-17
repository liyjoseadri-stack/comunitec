@props(['titulo'])

@if (isset($acciones))
    <header {{ $attributes->class('encabezado-pagina encabezado-pagina--acciones') }}>
        <div class="grupo-acciones">
            {{ $acciones }}
        </div>
    </header>
@endif
