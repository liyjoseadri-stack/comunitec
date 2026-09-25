@if ($paginador->hasPages())
    <nav class="paginacion" aria-label="Paginación de resultados">
        @if ($paginador->onFirstPage())
            <span class="boton boton--contorno boton--compacto" aria-disabled="true">Anterior</span>
        @else
            <a class="boton boton--contorno boton--compacto" href="{{ $paginador->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span class="paginacion__estado">Página {{ $paginador->currentPage() }} de {{ $paginador->lastPage() }}</span>

        @if ($paginador->hasMorePages())
            <a class="boton boton--contorno boton--compacto" href="{{ $paginador->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="boton boton--contorno boton--compacto" aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
