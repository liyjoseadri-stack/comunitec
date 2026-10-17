            @if ($cotizacion->notas)
                <section class="bloque-administrativo" aria-labelledby="titulo-notas-cotizacion">
                    <h2 id="titulo-notas-cotizacion">Notas y términos comerciales</h2>
                    <p>{{ $cotizacion->notas }}</p>
                </section>
            @endif
