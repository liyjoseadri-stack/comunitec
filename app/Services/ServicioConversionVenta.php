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
                ->with('cliente', 'partidas.producto', 'partidas.servicio')
                ->firstOrFail();

            $this->validarCotizacion($cotizacion);

            $seleccion = $this->validarSeries($cotizacion, $series);

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
                    'producto_id' => $partidaCotizada->producto_id,
                    'servicio_id' => $partidaCotizada->servicio_id,
                    'tipo' => $partidaCotizada->tipo,
                    'descripcion' => $partidaCotizada->descripcion,
                    'cantidad' => $partidaCotizada->cantidad,
                    'precio_unitario' => $partidaCotizada->precio_unitario,
                    'subtotal' => $partidaCotizada->subtotal,
                ]);

                foreach ($seleccion[$partidaCotizada->id] ?? [] as $numeroSerie) {
                    $piezaAnterior = PiezaInventario::whereRaw(
                        'LOWER(numero_serie) = ?',
                        [mb_strtolower($numeroSerie)]
                    )
                        ->where('producto_id', $partidaCotizada->producto_id)
                        ->where('cotizacion_id', $cotizacion->id)
                        ->where('estado', 'reservada')
                        ->lockForUpdate()
                        ->first();

                    if ($piezaAnterior !== null) {
                        $piezaAnterior->update([
                            'estado' => 'entregada',
                            'cotizacion_id' => null,
                            'partida_venta_id' => $partidaVendida->id,
                        ]);

                        continue;
                    }

                    $partidaVendida->piezas()->create([
                        'producto_id' => $partidaCotizada->producto_id,
                        'numero_serie' => $numeroSerie,
                        'estado' => 'entregada',
                        'cotizacion_id' => null,
                    ]);
                }
            }

            $cotizacion->update([
                'estado' => Cotizacion::ESTADO_VENTA,
                'entrega_limite_en' => null,
            ]);

            return $venta->load('partidas.piezas');
        });
    }

    private function validarCotizacion(Cotizacion $cotizacion): void
    {
        if ($cotizacion->venta()->exists()) {
            throw ValidationException::withMessages([
                'cotizacion' => 'Esta cotización ya fue convertida en venta.',
            ]);
        }
        if ($cotizacion->estado !== 'aceptada') {
            throw ValidationException::withMessages([
                'cotizacion' => 'Solo una cotización aceptada puede convertirse en venta.',
            ]);
        }
        $limiteEntrega = $cotizacion->fechaLimiteEntrega();
        if ($limiteEntrega === null || $limiteEntrega->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'cotizacion' => 'La reserva de la cotización venció; no puede convertirse en venta.',
            ]);
        }
    }

    private function validarSeries(
        Cotizacion $cotizacion,
        array $series
    ): array {
        $seleccion = [];
        $normalizadas = [];
        $partidasSerializables = $cotizacion->partidas->filter(
            fn (PartidaCotizacion $partida): bool => $partida->tipo === 'producto'
                && (bool) $partida->articulo?->requiere_numero_serie
        );

        foreach (array_keys($series) as $partidaId) {
            if (! $partidasSerializables->contains('id', (int) $partidaId)) {
                throw ValidationException::withMessages([
                    "series.{$partidaId}" => 'Esta partida no requiere números de serie.',
                ]);
            }
        }

        foreach ($partidasSerializables as $partida) {
            $numeros = array_map(
                fn ($numero): string => trim((string) $numero),
                $series[$partida->id] ?? []
            );
            $cantidad = (int) $partida->cantidad;

            if (count($numeros) !== $cantidad) {
                $this->fallarSeries(
                    $partida,
                    "Captura exactamente {$cantidad} números de serie."
                );
            }

            foreach ($numeros as $numero) {
                if ($numero === '') {
                    $this->fallarSeries($partida, 'Los números de serie no pueden estar vacíos.');
                }

                $clave = mb_strtolower($numero);
                if (isset($normalizadas[$clave])) {
                    $this->fallarSeries($partida, 'Un número de serie no puede asignarse más de una vez.');
                }
                $normalizadas[$clave] = true;

                $existente = PiezaInventario::whereRaw('LOWER(numero_serie) = ?', [$clave])
                    ->lockForUpdate()
                    ->first();
                $esReservaAnterior = $existente !== null
                    && $existente->cotizacion_id === $cotizacion->id
                    && $existente->producto_id === $partida->producto_id
                    && $existente->estado === 'reservada';
                if ($existente !== null && ! $esReservaAnterior) {
                    $this->fallarSeries($partida, "El número de serie {$numero} ya está registrado.");
                }
            }

            $seleccion[$partida->id] = $numeros;
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
