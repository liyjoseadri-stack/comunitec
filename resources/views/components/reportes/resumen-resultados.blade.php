@props([
    'cantidad',
    'etiquetaTotal',
    'tituloId',
    'total',
])

<div class="encabezado-seccion resumen-resultados">
    <div>
        <h2 id="{{ $tituloId }}">Resultados</h2>
        <span class="contador-registros">
            {{ $cantidad }} {{ $cantidad === 1 ? 'resultado' : 'resultados' }}
        </span>
    </div>
    <strong class="resumen-resultados__total">{{ $etiquetaTotal }}: ${{ number_format($total, 2) }}</strong>
</div>
