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
                ->with('cliente', 'partidas')
                ->firstOrFail();

            $this->validarCotizacion($cotizacion);

            $piezasReservadas = PiezaInventario::where('cotizacion_id', $cotizacion->id)
                ->where('estado', 'reservada')
                ->lockForUpdate()
                ->get();
            $seleccion = $this->validarSeries(
                $cotizacion,
                $piezasReservadas,
                $series
            );

            $venta = Venta::create([
                'folio' => 'VEN-'.now()->format('Ymd').'-'.Str::ulid(),
                'cotizacion_id' => $cotizacion->id,
                'cliente_id' => $cotizacion->cliente_id,
                'tipo_cliente' => $cotizacion->cliente->tipo,
                'nombre_cliente' => $cotizacion->cliente->nombre,
                'rfc_cliente' => $cotizacion->cliente->rfc,
                'correo_cliente' => $cotizacion->cliente->correo,
                'telefono_cliente' => $cotizacion->cliente->telefono,
                'direccion_cliente' => $cotizacion->cliente->direccion,
                'codigo_postal_cliente' => $cotizacion->cliente->codigo_postal,
                'usuario_id' => $usuario->id,
                'nombre_responsable' => $usuario->nombre,
                'correo_responsable' => $usuario->correo,
                'vendida_en' => now(),
                'metodo_pago' => $metodoPago,
                'detalle_metodo_pago' => $metodoPago === Venta::METODO_OTRO
                    ? $detalleMetodoPago
                    : null,
                'subtotal' => $cotizacion->partidas->sum('subtotal'),
                'porcentaje_descuento' => $cotizacion->porcentaje_descuento,
                'total' => $cotizacion->total,
            ]);

            foreach ($cotizacion->partidas as $partidaCotizada) {
                $partidaVendida = $venta->partidas()->create([
                    'partida_cotizacion_id' => $partidaCotizada->id,
                    'articulo_catalogo_id' => $partidaCotizada->articulo_catalogo_id,
                    'tipo' => $partidaCotizada->tipo,
                    'descripcion' => $partidaCotizada->descripcion,
                    'cantidad' => $partidaCotizada->cantidad,
                    'precio_unitario' => $partidaCotizada->precio_unitario,
                    'subtotal' => $partidaCotizada->subtotal,
                ]);

                if ($partidaCotizada->tipo === 'producto') {
                    PiezaInventario::whereIn('id', $seleccion[$partidaCotizada->id])
                        ->update([
                            'estado' => 'entregada',
                            'cotizacion_id' => null,
                            'partida_venta_id' => $partidaVendida->id,
                        ]);
                }
            }

            $cotizacion->update([
                'vence_en' => null,
            ]);

            return $venta->load('partidas.piezas');
        });
    }

    private function validarCotizacion(Cotizacion $cotizacion): void
    {
        if ($cotizacion->estado !== 'aceptada') {
            throw ValidationException::withMessages([
                'cotizacion' => 'Solo una cotización aceptada puede convertirse en venta.',
            ]);
        }
        if ($cotizacion->venta()->exists()) {
            throw ValidationException::withMessages([
                'cotizacion' => 'Esta cotización ya fue convertida en venta.',
            ]);
        }
        if ($cotizacion->vence_en === null || $cotizacion->vence_en->lessThanOrEqualTo(now())) {
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

        foreach ($cotizacion->partidas->where('tipo', 'producto') as $partida) {
            $identificadores = array_map(
                'intval',
                $series[$partida->id] ?? []
            );
            $cantidad = (int) $partida->cantidad;

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
                if ($pieza === null || $pieza->articulo_catalogo_id !== $partida->articulo_catalogo_id) {
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
