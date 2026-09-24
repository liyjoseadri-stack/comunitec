<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            @page {
                margin: 28px 32px 42px;
            }

            body {
                color: #172033;
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                line-height: 1.45;
            }

            h1,
            h2,
            p {
                margin-top: 0;
            }

            h1 {
                margin-bottom: 3px;
                color: #0f172a;
                font-size: 21px;
            }

            h2 {
                margin-bottom: 10px;
                font-size: 15px;
            }

            .encabezado {
                width: 100%;
                margin-bottom: 20px;
                border-bottom: 2px solid #0f172a;
            }

            .encabezado td {
                padding-bottom: 12px;
                border: 0;
                vertical-align: top;
            }

            .encabezado .folio {
                text-align: right;
            }

            .subtitulo {
                color: #475569;
            }

            .datos {
                width: 100%;
                margin-bottom: 18px;
                border-collapse: separate;
                border-spacing: 8px 0;
            }

            .datos td {
                width: 50%;
                padding: 10px;
                border: 1px solid #cbd5e1;
                vertical-align: top;
            }

            .datos strong {
                display: inline-block;
                margin-bottom: 4px;
            }

            .partidas {
                width: 100%;
                border-collapse: collapse;
            }

            .partidas th,
            .partidas td {
                padding: 7px;
                border: 1px solid #cbd5e1;
            }

            .partidas th {
                color: #ffffff;
                background: #0f172a;
                text-align: left;
            }

            .partidas .numero {
                text-align: right;
                white-space: nowrap;
            }

            .resumen {
                width: 44%;
                margin-top: 14px;
                margin-left: auto;
                border-collapse: collapse;
            }

            .resumen th,
            .resumen td {
                padding: 5px 7px;
                border-bottom: 1px solid #cbd5e1;
            }

            .resumen th {
                text-align: left;
            }

            .resumen td {
                text-align: right;
            }

            .resumen .total {
                color: #ffffff;
                background: #0f172a;
                font-size: 13px;
            }

            .nota {
                margin-top: 18px;
                padding: 9px;
                color: #334155;
                background: #f1f5f9;
            }

            .pie {
                position: fixed;
                right: 0;
                bottom: -27px;
                left: 0;
                color: #64748b;
                font-size: 9px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        @php
            $subtotal = (float) $quote->lines->sum('subtotal');
            $descuento = $subtotal - (float) $quote->total;
            $fechaDocumento = $quote->sent_at ?? $quote->created_at;
        @endphp

        <table class="encabezado">
            <tr>
                <td>
                    <h1>COMUN&amp;TEC</h1>
                    <p class="subtitulo">Soluciones e infraestructura tecnológica</p>
                </td>
                <td class="folio">
                    <h2>Cotización</h2>
                    <strong>{{ $quote->folio }}</strong>
                    <br>
                    {{ $fechaDocumento?->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        <table class="datos">
            <tr>
                <td>
                    <strong>Cliente</strong>
                    <br>
                    {{ $quote->customer->name }}
                    <br>
                    RFC: {{ $quote->customer->rfc }}
                    <br>
                    {{ $quote->customer->address }}, C.P. {{ $quote->customer->postal_code }}
                    <br>
                    {{ $quote->customer->email }} · {{ $quote->customer->phone }}
                </td>
                <td>
                    <strong>Datos de la cotización</strong>
                    <br>
                    Estado: {{ $quote->etiquetaEstado() }}
                    <br>
                    Responsable: {{ $quote->responsable?->name ?? 'Sin asignar' }}
                    @if ($quote->area_requesting)
                        <br>
                        Área solicitante: {{ $quote->area_requesting }}
                    @endif
                    @if ($quote->expires_at)
                        <br>
                        Vigencia hasta: {{ $quote->expires_at->format('d/m/Y') }}
                    @endif
                </td>
            </tr>
        </table>

        <table class="partidas">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="numero">Cantidad</th>
                    <th class="numero">Precio unitario</th>
                    <th class="numero">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quote->lines as $line)
                    <tr>
                        <td>{{ $line->description }}</td>
                        <td class="numero">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td class="numero">${{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="numero">${{ number_format((float) $line->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Esta cotización todavía no contiene partidas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="resumen">
            <tr>
                <th>Subtotal</th>
                <td>${{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <th>Descuento ({{ number_format((float) $quote->discount_percent, 2) }}%)</th>
                <td>-${{ number_format($descuento, 2) }}</td>
            </tr>
            <tr class="total">
                <th>Total</th>
                <td>${{ number_format((float) $quote->total, 2) }}</td>
            </tr>
        </table>

        <p class="nota">
            Todos los precios mostrados incluyen IVA. Esta cotización está sujeta a la vigencia indicada.
        </p>

        <div class="pie">
            Cotización generada por el Sistema Administrativo COMUN&amp;TEC.
        </div>
    </body>
</html>
