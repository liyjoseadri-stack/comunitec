@props([
    'accion',
    'filtros',
    'clientes',
    'responsables',
    'tituloId',
])

<section class="bloque-administrativo" aria-labelledby="{{ $tituloId }}">
    <h2 id="{{ $tituloId }}">Filtros</h2>
    <form
        class="formulario-filtros"
        method="GET"
        action="{{ $accion }}"
        role="search"
        aria-labelledby="{{ $tituloId }}"
    >
        <x-campo-formulario
            id="desde"
            nombre="desde"
            etiqueta="Desde"
            tipo="date"
            contenedor="campo-filtro"
            :valor="$filtros['desde']"
        />
        <x-campo-formulario
            id="hasta"
            nombre="hasta"
            etiqueta="Hasta"
            tipo="date"
            contenedor="campo-filtro"
            :valor="$filtros['hasta']"
        />
        <x-campo-formulario id="cliente" nombre="cliente" etiqueta="Cliente" tipo="select" contenedor="campo-filtro">
            <option value="">Todos</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected(($filtros['cliente'] ?? null) == $cliente->id)>
                    {{ $cliente->nombre }}
                </option>
            @endforeach
        </x-campo-formulario>
        <x-campo-formulario id="responsable" nombre="responsable" etiqueta="Responsable" tipo="select" contenedor="campo-filtro">
            <option value="">Todos</option>
            @foreach ($responsables as $responsable)
                <option value="{{ $responsable->id }}" @selected(($filtros['responsable'] ?? null) == $responsable->id)>
                    {{ $responsable->nombre }}
                </option>
            @endforeach
        </x-campo-formulario>

        {{ $slot }}

        <div class="acciones-formulario">
            <x-boton tipo="submit">Aplicar filtros</x-boton>
            <x-boton variante="contorno" :href="$accion">Limpiar filtros</x-boton>
        </div>
    </form>
</section>
