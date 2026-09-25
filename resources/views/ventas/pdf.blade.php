<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 36px 38px; }
        body { color: #172b2f; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        .marca-agua { position: fixed; right: -18px; bottom: 10px; width: 300px; z-index: -1000; }
        .encabezado { position: relative; min-height: 125px; text-align: center; }
        .logotipo { width: 235px; }
        .folio { position: absolute; top: 10px; right: 0; width: 170px; color: #475569; text-align: right; }
        .folio strong { display: block; color: #0f6f70; font-size: 13px; }
        .titulo { margin: 8px 0 15px; color: #0f5558; font-size: 16px; text-align: center; }
        .datos { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .datos td { width: 50%; padding: 4px 8px; border-bottom: 1px solid #d6e1e2; vertical-align: top; }
        .datos strong { color: #0f5558; }
        .partidas { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .partidas th, .partidas td { padding: 6px 5px; border: 1px solid #8ba5a7; vertical-align: top; }
        .partidas th { color: #fff; background: #0f6f70; font-size: 8px; text-align: center; }
        .partidas tr { page-break-inside: avoid; }
        .tipo { width: 9%; text-align: center; }
        .concepto { width: 19%; }
        .descripcion { width: 35%; }
        .cantidad { width: 8%; text-align: center; }
        .dinero { width: 13%; text-align: right; white-space: nowrap; }
        .serie { display: block; margin-bottom: 2px; font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
        .lista-series { margin-top: 5px; padding-top: 5px; border-top: 1px solid #d6e1e2; }
        .secundario { color: #64748b; font-size: 8px; }
        .totales { width: 45%; margin: 12px 0 0 auto; }
        .totales td { padding: 3px 0; text-align: right; }
        .totales .etiqueta { font-weight: 700; }
        .total td { padding-top: 6px; border-top: 2px solid #0f6f70; color: #0f6f70; font-size: 11px; font-weight: 700; }
        .nota { margin-top: 20px; color: #475569; }
        .pie { position: fixed; right: 0; bottom: -24px; left: 0; color: #64748b; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
    @php
        $subtotalAntesDescuento = (float) $venta->subtotal;
        $total = (float) $venta->total;
        $descuento = max(0, $subtotalAntesDescuento - $total);
        $subtotalSinIva = $total / 1.16;
        $ivaIncluido = $total - $subtotalSinIva;
    @endphp

    <img class="marca-agua" src="{{ public_path('images/nube-comunitec-marca-agua-pdf.jpg') }}" alt="">

    <header class="encabezado">
        <img class="logotipo" src="{{ public_path('images/logo-comunitec-pdf.jpg') }}" alt="COMUN&TEC">
        <div class="folio">
            Venta
            <strong>{{ $venta->folio }}</strong>
            Cotización: {{ $venta->cotizacion->folio }}
        </div>
    </header>

    <h1 class="titulo">Comprobante administrativo de venta</h1>

    <table class="datos">
        <tr>
            <td><strong>Cliente:</strong> {{ $venta->nombreClienteMostrado() }}</td>
            <td><strong>Fecha:</strong> {{ $venta->vendida_en->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>RFC:</strong> {{ $venta->rfc_cliente }}</td>
            <td><strong>Método de pago:</strong> {{ $venta->etiquetaMetodoPago() }}</td>
        </tr>
        <tr>
            <td><strong>Correo:</strong> {{ $venta->correo_cliente }}</td>
            <td><strong>Responsable:</strong> {{ $venta->nombreResponsableMostrado() }}</td>
        </tr>
    </table>

    <table class="partidas">
        <thead>
            <tr>
                <th class="tipo">TIPO</th>
                <th class="concepto">CONCEPTO</th>
                <th class="descripcion">DESCRIPCIÓN</th>
                <th class="cantidad">CANT.</th>
                <th class="dinero">PRECIO</th>
                <th class="dinero">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($venta->partidas as $partida)
                <tr>
                    <td class="tipo">{{ ucfirst($partida->tipo) }}</td>
                    <td class="concepto">
                        {{ $partida->articulo?->nombre ?? 'Concepto libre' }}
                        @if ($partida->articulo?->codigo)
                            <div class="secundario">SKU: {{ $partida->articulo->codigo }}</div>
                        @endif
                    </td>
                    <td class="descripcion">
                        {{ $partida->descripcion }}
                        @if ($partida->tipo === 'producto' && $partida->piezas->isNotEmpty())
                            <div class="lista-series">
                                <strong>Números de serie:</strong>
                                @foreach ($partida->piezas as $pieza)
                                    <span class="serie">• {{ $pieza->numero_serie }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="cantidad">{{ number_format((float) $partida->cantidad, 2) }}</td>
                    <td class="dinero">${{ number_format((float) $partida->precio_unitario, 2) }}</td>
                    <td class="dinero">${{ number_format((float) $partida->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td class="etiqueta">Descuento ({{ number_format((float) $venta->porcentaje_descuento, 2) }}%):</td><td>-${{ number_format($descuento, 2) }}</td></tr>
        <tr><td class="etiqueta">Subtotal sin IVA:</td><td>${{ number_format($subtotalSinIva, 2) }}</td></tr>
        <tr><td class="etiqueta">IVA incluido:</td><td>${{ number_format($ivaIncluido, 2) }}</td></tr>
        <tr class="total"><td>Total:</td><td>${{ number_format($total, 2) }}</td></tr>
    </table>

    <p class="nota">
        Este documento registra administrativamente la venta y los equipos entregados. Los precios incluyen IVA.
    </p>

    <div class="pie">
        COMUN&amp;TEC · Comercialización e instalación de tecnologías · {{ $venta->folio }}
    </div>
</body>
</html>
