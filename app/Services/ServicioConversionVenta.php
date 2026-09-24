<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PiezaInventario;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServicioConversionVenta
{
    public function convertir(
        Cotizacion $cotizacion,
        Usuario $usuario,
        string $metodoPago,
        ?string $detalleMetodoPago,
        array $series
    ): Venta {
        return DB::transaction(function () use (
            $cotizacion,
            $usuario,
            $metodoPago,
            $detalleMetodoPago,
            $series
        ): Venta {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)
                ->lockForUpdate()
                ->with('customer', 'lines')
                ->firstOrFail();

            $this->validarCotizacion($cotizacion);

            $piezasReservadas = PiezaInventario::where('quote_id', $cotizacion->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();
            $seleccion = $this->validarSeries(
                $cotizacion,
                $piezasReservadas,
                $series
            );

            $venta = Venta::create([
                'folio' => 'VEN-'.now()->format('Ymd').'-'.Str::ulid(),
                'quote_id' => $cotizacion->id,
                'customer_id' => $cotizacion->customer_id,
                'customer_type' => $cotizacion->customer->type,
                'customer_name' => $cotizacion->customer->name,
                'customer_rfc' => $cotizacion->customer->rfc,
                'customer_email' => $cotizacion->customer->email,
                'customer_phone' => $cotizacion->customer->phone,
                'customer_address' => $cotizacion->customer->address,
                'customer_postal_code' => $cotizacion->customer->postal_code,
                'user_id' => $usuario->id,
                'responsible_name' => $usuario->name,
                'responsible_email' => $usuario->email,
                'sold_at' => now(),
                'payment_method' => $metodoPago,
                'payment_method_detail' => $metodoPago === Venta::METODO_OTRO
                    ? $detalleMetodoPago
                    : null,
                'subtotal' => $cotizacion->lines->sum('subtotal'),
                'discount_percent' => $cotizacion->discount_percent,
                'total' => $cotizacion->total,
            ]);

            foreach ($cotizacion->lines as $partidaCotizada) {
                $partidaVendida = $venta->partidas()->create([
                    'quote_line_id' => $partidaCotizada->id,
                    'catalog_item_id' => $partidaCotizada->catalog_item_id,
                    'type' => $partidaCotizada->type,
                    'description' => $partidaCotizada->description,
                    'quantity' => $partidaCotizada->quantity,
                    'unit_price' => $partidaCotizada->unit_price,
                    'subtotal' => $partidaCotizada->subtotal,
                ]);

                if ($partidaCotizada->type === 'product') {
                    PiezaInventario::whereIn('id', $seleccion[$partidaCotizada->id])
                        ->update([
                            'status' => 'delivered',
                            'quote_id' => null,
                            'sale_line_id' => $partidaVendida->id,
                        ]);
                }
            }

            $cotizacion->update([
                'expires_at' => null,
            ]);

            return $venta->load('partidas.piezas');
        });
    }

    private function validarCotizacion(Cotizacion $cotizacion): void
    {
        if ($cotizacion->status !== 'accepted') {
            throw ValidationException::withMessages([
                'cotizacion' => 'Solo una cotización aceptada puede convertirse en venta.',
            ]);
        }
        if ($cotizacion->venta()->exists()) {
            throw ValidationException::withMessages([
                'cotizacion' => 'Esta cotización ya fue convertida en venta.',
            ]);
        }
        if ($cotizacion->expires_at === null || $cotizacion->expires_at->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'cotizacion' => 'La reserva de la cotización venció; no puede convertirse en venta.',
            ]);
        }
    }

    private function validarSeries(
        Cotizacion $cotizacion,
        $piezasReservadas,
        array $series
    ): array {
        $seleccion = [];
        $piezasUsadas = [];

        foreach ($cotizacion->lines->where('type', 'product') as $partida) {
            $identificadores = array_map(
                'intval',
                $series[$partida->id] ?? []
            );
            $cantidad = (int) $partida->quantity;

            if (count($identificadores) !== $cantidad) {
                $this->fallarSeries(
                    $partida,
                    "Selecciona exactamente {$cantidad} series."
                );
            }

            foreach ($identificadores as $identificador) {
                if (in_array($identificador, $piezasUsadas, true)) {
                    $this->fallarSeries($partida, 'Una serie no puede asignarse más de una vez.');
                }

                $pieza = $piezasReservadas->firstWhere('id', $identificador);
                if ($pieza === null || $pieza->catalog_item_id !== $partida->catalog_item_id) {
                    $this->fallarSeries(
                        $partida,
                        'Todas las series deben estar reservadas para esta cotización y corresponder al producto.'
                    );
                }
                $piezasUsadas[] = $identificador;
            }

            $seleccion[$partida->id] = $identificadores;
        }

        if (count($piezasUsadas) !== $piezasReservadas->count()) {
            throw ValidationException::withMessages([
                'series' => 'Las series seleccionadas no coinciden con todas las piezas reservadas.',
            ]);
        }

        return $seleccion;
    }

    private function fallarSeries(PartidaCotizacion $partida, string $mensaje): never
    {
        throw ValidationException::withMessages([
            "series.{$partida->id}" => $mensaje,
        ]);
    }
}
