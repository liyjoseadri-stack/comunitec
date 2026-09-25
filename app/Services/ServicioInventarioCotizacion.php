<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\Producto;
use App\Support\PlazosHabiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ServicioInventarioCotizacion
{
    public function reservar(Cotizacion $cotizacion): void
    {
        DB::transaction(function () use ($cotizacion): void {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            if ($cotizacion->estado !== 'pendiente') {
                throw ValidationException::withMessages([
                    'cotizacion' => 'Solo se puede aceptar una cotización pendiente.',
                ]);
            }
            if ($cotizacion->vence_en !== null && $cotizacion->vence_en->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'cotizacion' => 'La vigencia de la cotización terminó; no puede aceptarse.',
                ]);
            }
            $cantidades = $this->cantidadesReservadas($cotizacion);
            $articulos = Producto::whereKey($cantidades->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cantidades as $articuloId => $cantidad) {
                $articulo = $articulos->get($articuloId);
                if ($articulo === null || $articulo->existencias < $cantidad) {
                    throw new RuntimeException('Existencias insuficientes.');
                }
            }

            foreach ($cantidades as $articuloId => $cantidad) {
                $articulos->get($articuloId)->decrement('existencias', $cantidad);
            }

            $cotizacion->update([
                'estado' => 'aceptada',
                'aceptada_en' => now(),
                'entrega_limite_en' => PlazosHabiles::sumar(now(), 5),
            ]);
        });
    }

    public function liberar(Cotizacion $cotizacion, bool $soloSiVencida = false): bool
    {
        return $this->liberarConEstado($cotizacion, 'cancelada', $soloSiVencida);
    }

    public function liberarParaEdicion(Cotizacion $cotizacion): bool
    {
        return $this->liberarConEstado($cotizacion, 'pendiente');
    }

    private function liberarConEstado(
        Cotizacion $cotizacion,
        string $estadoDestino,
        bool $soloSiVencida = false
    ): bool {
        return DB::transaction(function () use ($cotizacion, $estadoDestino, $soloSiVencida): bool {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            if ($cotizacion->estado !== 'aceptada') {
                return false;
            }
            $limiteEntrega = $cotizacion->fechaLimiteEntrega();
            if ($soloSiVencida && ($limiteEntrega === null || $limiteEntrega->isFuture())) {
                return false;
            }

            $cantidades = $this->cantidadesReservadas($cotizacion);
            $articulos = Producto::whereKey($cantidades->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cantidades as $articuloId => $cantidad) {
                $articulos->get($articuloId)?->increment('existencias', $cantidad);
            }

            $cotizacion->update([
                'estado' => $estadoDestino,
                'aceptada_en' => $estadoDestino === 'pendiente' ? null : $cotizacion->aceptada_en,
                'entrega_limite_en' => $estadoDestino === 'pendiente' ? null : $cotizacion->entrega_limite_en,
            ]);

            return true;
        });
    }

    private function cantidadesReservadas(Cotizacion $cotizacion)
    {
        return $cotizacion->partidas()
            ->where('tipo', 'producto')
            ->selectRaw('producto_id, SUM(cantidad) AS cantidad')
            ->groupBy('producto_id')
            ->pluck('cantidad', 'producto_id')
            ->map(fn ($cantidad): int => (int) $cantidad);
    }
}
