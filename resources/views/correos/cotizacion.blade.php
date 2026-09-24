<p>
    Hola, {{ $quote->customer->name }}.
</p>
<p>
    Adjuntamos la cotización
    <strong>
        {{ $quote->folio }}
    </strong>
    para su revisión.
</p>
<p>
    El importe total es de
    <strong>
        ${{ $quote->total }}
    </strong>
    , IVA incluido.
</p>
<p>
    Atentamente,
    <br>
    COMUN&amp;TEC
</p>
