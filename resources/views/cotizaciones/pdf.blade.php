<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 36px 34px; }
        body { color: #171717; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.42; }
        p { margin: 0 0 12px; }
        .marca-agua { position: fixed; right: -18px; bottom: 18px; width: 310px; z-index: -1000; }
        .encabezado { position: relative; min-height: 132px; margin-bottom: 12px; text-align: center; }
        .logotipo { width: 245px; height: auto; }
        .folio { position: absolute; top: 10px; right: 0; width: 165px; color: #374151; font-size: 9px; text-align: right; }
        .folio strong { display: block; color: #0f6f70; font-size: 12px; }
        .fecha { margin-bottom: 15px; font-size: 11px; text-align: right; }
        .destinatario { margin-bottom: 14px; font-size: 11px; }
        .introduccion { margin-bottom: 16px; font-size: 10.5px; }
        .partidas { width: 100%; margin-bottom: 0; border-collapse: collapse; table-layout: fixed; }
        .partidas th, .partidas td { padding: 7px 6px; border: 1px solid #4b5563; vertical-align: middle; }
        .partidas th { color: #fff; background: #0f6f70; font-size: 9px; letter-spacing: .2px; text-align: center; }
        .partidas tr { page-break-inside: avoid; }
        .cantidad { width: 9%; text-align: center; }
        .descripcion { width: 24%; font-weight: 700; }
        .caracteristicas { width: 35%; }
        .importe { width: 16%; text-align: right; white-space: nowrap; }
        .detalle-secundario { color: #4b5563; font-size: 8.5px; }
        .totales { width: 47%; margin: 10px 0 18px auto; text-align: right; }
        .linea-total { padding: 3px 0; white-space: nowrap; }
        .etiqueta-total { display: inline-block; width: 135px; font-weight: 700; }
        .valor-total { display: inline-block; width: 90px; }
        .linea-total-principal { margin-top: 2px; padding-top: 6px; border-top: 2px solid #0f6f70; color: #0f6f70; font-size: 11px; }
        .nota-iva { margin-top: -10px; color: #4b5563; font-size: 8.5px; text-align: right; }
        .cierre { max-width: 620px; margin-top: 18px; font-size: 10px; }
        .firma { margin-top: 42px; line-height: 1.35; }
        .firma strong { display: block; font-size: 11px; letter-spacing: .4px; }
        .puesto { font-size: 8.5px; letter-spacing: 1px; }
        .contacto { margin-top: 12px; font-size: 8.5px; line-height: 1.35; }
        .pie { position: fixed; right: 0; bottom: -21px; left: 0; color: #6b7280; font-size: 7.5px; text-align: center; }
    </style>
</head>
<body>
    @php
        $subtotalPartidas = (float) $cotizacion->partidas->sum('subtotal');
        $total = (float) $cotizacion->total;
        $descuento = max(0, $subtotalPartidas - $total);
        $subtotalSinIva = $total / 1.16;
        $ivaIncluido = $total - $subtotalSinIva;
        $fechaDocumento = $cotizacion->enviada_en ?? $cotizacion->creado_en;
    @endphp

    <img class="marca-agua" src="{{ public_path('images/nube-comunitec-marca-agua-pdf.jpg') }}" alt="">

    <header class="encabezado">
        <img class="logotipo" src="{{ public_path('images/logo-comunitec-pdf.jpg') }}" alt="COMUN&TEC">
        <div class="folio">
            Cotización
            <strong>{{ $cotizacion->folio }}</strong>
            Estado: {{ $cotizacion->etiquetaEstado() }}
        </div>
    </header>

    <p class="fecha">
        Tuxtla Gutiérrez, Chiapas a {{ $fechaDocumento?->locale('es')->translatedFormat('j \d\e F \d\e Y') }}.
    </p>

    <p class="destinatario">
        Para <strong>{{ $cotizacion->cliente->nombre }}</strong>
        @if ($cotizacion->area_solicitante)
            · Área: {{ $cotizacion->area_solicitante }}
        @endif
    </p>

    <p class="introduccion">
        En atención a su amable solicitud, me permito enviarle la cotización correspondiente
        a los productos y servicios de su interés:
    </p>

    <table class="partidas">
        <thead>
            <tr>
                <th class="cantidad">CANTIDAD</th>
                <th class="descripcion">DESCRIPCIÓN</th>
                <th class="caracteristicas">CARACTERÍSTICAS</th>
                <th class="importe">PRECIO UNITARIO</th>
                <th class="importe">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cotizacion->partidas as $partida)
                @php
                    $articulo = $partida->articulo;
                    $caracteristicas = collect([
                        $articulo?->marca ? 'Marca: '.$articulo->marca : null,
                        $articulo?->modelo ? 'Modelo: '.$articulo->modelo : null,
                        $articulo?->codigo ? 'Código: '.$articulo->codigo : null,
                        $articulo?->unidad ? 'Unidad: '.$articulo->unidad : null,
                    ])->filter();
                @endphp
                <tr>
                    <td class="cantidad">{{ number_format((float) $partida->cantidad, 2) }}</td>
                    <td class="descripcion">{{ $partida->descripcion }}</td>
                    <td class="caracteristicas">
                        @if ($caracteristicas->isNotEmpty())
                            {{ $caracteristicas->join(' · ') }}
                        @else
                            <span class="detalle-secundario">
                                {{ $partida->tipo === 'servicio' ? 'Servicio cotizado' : 'Especificación indicada en la descripción' }}
                            </span>
                        @endif
                    </td>
                    <td class="importe">${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                    <td class="importe">${{ number_format((float) $partida->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Esta cotización todavía no contiene partidas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totales">
        <div class="linea-total">
            <span class="etiqueta-total">
                DESCUENTO ({{ number_format((float) $cotizacion->porcentaje_descuento, 2) }}%):
            </span>
            <span class="valor-total">{{ $descuento > 0 ? '-' : '' }}${{ number_format($descuento, 2) }}</span>
        </div>
        <div class="linea-total">
            <span class="etiqueta-total">SUBTOTAL:</span>
            <span class="valor-total">${{ number_format($subtotalSinIva, 2) }}</span>
        </div>
        <div class="linea-total">
            <span class="etiqueta-total">IVA:</span>
            <span class="valor-total">${{ number_format($ivaIncluido, 2) }}</span>
        </div>
        <div class="linea-total linea-total-principal">
            <span class="etiqueta-total">TOTAL:</span>
            <span class="valor-total">${{ number_format($total, 2) }}</span>
        </div>
    </div>

    <p class="nota-iva">Los precios ya incluyen IVA; el desglose es informativo y no se suma nuevamente.</p>

    <p class="cierre">
        Esperando que el contenido de la presente sea de su utilidad, me pongo a sus órdenes
        para cualquier duda o aclaración al respecto. <strong>Tiempo de entrega: 5 días hábiles.</strong>
        @if ($cotizacion->vence_en)
            Vigencia de la cotización hasta el {{ $cotizacion->vence_en->format('d/m/Y') }}.
        @endif
    </p>

    <section class="firma">
        <strong>L.I. RICARDO VELÁZQUEZ HERNÁNDEZ</strong>
        <span class="puesto">TECNOLOGÍAS DE LA INFORMACIÓN</span>
        <div class="contacto">
            9A. SUR PONIENTE 558 · COL. CENTRO<br>
            TUXTLA GUTIÉRREZ, CHIAPAS. · C.P. 29000<br>
            044 9611111955<br>
            servicios.comunitec@gmail.com
        </div>
    </section>

    <div class="pie">
        Responsable de la cotización: {{ $cotizacion->responsable?->nombre ?? 'Sin asignar' }}
        · {{ $cotizacion->cliente->rfc }} · {{ $cotizacion->cliente->correo }}
    </div>
</body>
</html>
