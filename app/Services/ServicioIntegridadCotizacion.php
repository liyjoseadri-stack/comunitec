<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Support\CalculosCotizacion;
use Illuminate\Validation\ValidationException;

class ServicioIntegridadCotizacion
{
    public function validarParaAvanzar(Cotizacion $cotizacion): void
    {
        // El llamador ya debe haber releído y bloqueado la cabecera en su transacción.
        $partidas = $cotizacion->partidas()->lockForUpdate()->get();
        $cotizacion->setRelation('partidas', $partidas);

        if ($partidas->isEmpty()) {
            $this->fallar('Agrega al menos un concepto antes de avanzar la cotización.');
        }

        $subtotal = 0.0;
        foreach ($partidas as $partida) {
            $cantidad = $this->numero($partida->getRawOriginal('cantidad'));
            $precio = $this->numero($partida->getRawOriginal('precio_unitario'));
            $descuento = $this->numero($partida->getRawOriginal('porcentaje_descuento'));
            $subtotalGuardado = $this->numero($partida->getRawOriginal('subtotal'));

            if ($cantidad <= 0 || ($partida->tipo === PartidaCotizacion::TIPO_PRODUCTO && floor($cantidad) !== $cantidad)) {
                $this->fallar('Los conceptos requieren cantidades positivas y los productos cantidades enteras.');
            }
            if ($precio < 0 || $descuento < 0 || $descuento > 100 || $subtotalGuardado < 0) {
                $this->fallar('Revisa los precios, descuentos y subtotales de los conceptos.');
            }

            $subtotalCalculado = CalculosCotizacion::descontar($cantidad * $precio, $descuento);
            if (! is_finite($subtotalCalculado) || $subtotalGuardado !== $subtotalCalculado) {
                $this->fallar('El subtotal de un concepto no coincide con su cantidad, precio y descuento.');
            }
            $subtotal += $subtotalCalculado;
        }

        $descuentoGeneral = $this->numero($cotizacion->getRawOriginal('porcentaje_descuento'));
        if ($descuentoGeneral !== 0.0 && ($descuentoGeneral < 5 || $descuentoGeneral > 10)) {
            $this->fallar('El descuento general debe ser 0 o estar entre 5% y 10%.');
        }

        $total = $this->numero($cotizacion->getRawOriginal('total'));
        $totalCalculado = CalculosCotizacion::descontar($subtotal, $descuentoGeneral);
        if (! is_finite($totalCalculado) || $total < 0 || $total !== $totalCalculado) {
            $this->fallar('El total guardado no coincide con los conceptos y el descuento general.');
        }
    }

    private function numero(mixed $valor): float
    {
        if (! is_numeric($valor) || ! is_finite((float) $valor)) {
            $this->fallar('La cotización contiene cantidades o importes inválidos.');
        }
        if (! CalculosCotizacion::tienePrecisionValida($valor)) {
            $this->fallar('Las cantidades, precios, descuentos e importes admiten como máximo dos decimales.');
        }

        return (float) $valor;
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['cotizacion' => $mensaje]);
    }
}
