<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: DejaVu Sans;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #333;
                padding: 6px;
            }

            th {
                text-align: left;
            }
        </style>
    </head>
    <body>
        <h1>
            COMUN&TEC
        </h1>
        <h2>
            Cotización {{ $quote->folio }}
        </h2>
        <p>
            Cliente: {{ $quote->customer->name }}
        </p>
        <p>
            Estado: {{ $quote->etiquetaEstado() }} | Descuento: {{ $quote->discount_percent }}%
        </p>
        <table>
            <thead>
                <tr>
                    <th>
                        Descripción
                    </th>
                    <th>
                        Cantidad
                    </th>
                    <th>
                        Precio unitario
                    </th>
                    <th>
                        Subtotal
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->lines as $line)
                    <tr>
                        <td>
                            {{ $line->description }}
                        </td>
                        <td>
                            {{ $line->quantity }}
                        </td>
                        <td>
                            ${{ $line->unit_price }}
                        </td>
                        <td>
                            ${{ $line->subtotal }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h3>
            Total: ${{ $quote->total }}
        </h3>
    </body>
</html>
