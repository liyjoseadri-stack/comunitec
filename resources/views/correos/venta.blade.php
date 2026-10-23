<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de venta {{ $venta->folio }}</title>
</head>
<body style="margin:0;background:#f3f8fa;font-family:Arial,sans-serif;color:#102a43;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f8fa;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #d6e3e8;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px;background:#006b6b;color:#ffffff;">
                            <div style="font-size:13px;font-weight:bold;letter-spacing:1px;">COMUN&amp;TEC</div>
                            <h1 style="margin:8px 0 0;font-size:24px;">Pago registrado</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px;line-height:1.6;">Hola, {{ $venta->nombreClienteMostrado() }}.</p>
                            <p style="margin:0 0 20px;line-height:1.6;">Confirmamos el registro de su pago. En este correo encontrará los productos y servicios adquiridos, así como los números de serie asignados. También adjuntamos el comprobante en PDF.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;border-collapse:collapse;background:#f7fafb;border-radius:8px;">
                                <tr><td style="padding:10px 14px;color:#526777;">Folio de venta</td><td style="padding:10px 14px;text-align:right;font-weight:bold;">{{ $venta->folio }}</td></tr>
                                <tr><td style="padding:10px 14px;color:#526777;border-top:1px solid #d6e3e8;">Método de pago</td><td style="padding:10px 14px;text-align:right;font-weight:bold;border-top:1px solid #d6e3e8;">{{ $venta->etiquetaMetodoPago() }}</td></tr>
                                <tr><td style="padding:10px 14px;color:#526777;border-top:1px solid #d6e3e8;">Total pagado</td><td style="padding:10px 14px;text-align:right;font-weight:bold;border-top:1px solid #d6e3e8;">${{ number_format((float) $venta->total, 2) }}</td></tr>
                            </table>

                            <h2 style="margin:0 0 12px;font-size:18px;">Detalle de la compra</h2>
                            @foreach ($venta->partidas as $partida)
                                <div style="padding:14px 0;border-top:1px solid #d6e3e8;">
                                    <div style="font-weight:bold;line-height:1.4;">{{ $partida->nombreHistorico() }}</div>
                                    <div style="margin-top:4px;color:#526777;font-size:14px;">Cantidad: {{ number_format((float) $partida->cantidad, 2) }} · Subtotal: ${{ number_format((float) $partida->subtotal, 2) }}</div>
                                    @if ($partida->piezas->isNotEmpty())
                                        <div style="margin-top:8px;font-size:14px;">
                                            <strong>Números de serie:</strong>
                                            {{ $partida->piezas->pluck('numero_serie')->join(', ') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <p style="margin:24px 0 0;line-height:1.6;color:#526777;">Conserve este mensaje y el PDF adjunto como referencia de su operación.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
