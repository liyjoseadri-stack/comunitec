<p>Hola, {{ $cotizacion->cliente->nombre }}.</p>

<p>
    La cotización <strong>{{ $cotizacion->folio }}</strong> está próxima a vencer.
    ¿Aún requiere los productos o servicios incluidos en ella?
</p>

<p>
    Su vigencia termina el
    <strong>{{ $cotizacion->vence_en->format('d/m/Y') }}</strong>.
    Si desea continuar, por favor comuníquese con COMUN&amp;TEC.
</p>

<p>Adjuntamos nuevamente la cotización para su revisión.</p>

<p>
    Atentamente,<br>
    COMUN&amp;TEC
</p>
