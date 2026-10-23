@props([
    'cantidad',
    'etiquetaTotal',
    'tituloId',
    'total',
])

<div class="encabezado-seccion resumen-resultados">
    <div>
        <h2 id="{{ $tituloId }}">Resultados <span class="contador-titulo">{{ $cantidad }}</span></h2>
    </div>
    <strong class="resumen-resultados__total">{{ $etiquetaTotal }}: ${{ number_format($total, 2) }}</strong>
</div>
