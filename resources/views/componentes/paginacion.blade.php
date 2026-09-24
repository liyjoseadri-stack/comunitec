@if ($paginador->hasPages())
    <nav class="paginacion" aria-label="Paginación de resultados">
        @if ($paginador->onFirstPage())
            <span aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginador->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span>Página {{ $paginador->currentPage() }} de {{ $paginador->lastPage() }}</span>

        @if ($paginador->hasMorePages())
            <a href="{{ $paginador->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
