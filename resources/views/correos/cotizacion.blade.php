<p>
    Hola, {{ $cotizacion->cliente->nombre }}.
</p>
<p>
    Adjuntamos la cotización
    <strong>
        {{ $cotizacion->folio }}
    </strong>
    para su revisión.
</p>
<p>
    El importe total es de
    <strong>
        ${{ $cotizacion->total }}
    </strong>
    , IVA incluido.
</p>
<p>
    Atentamente,
    <br>
    COMUN&amp;TEC
</p>
