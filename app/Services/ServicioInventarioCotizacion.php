<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PiezaInventario;
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
            foreach ($cotizacion->partidas()->where('tipo', 'producto')->get() as $partida) {
                $piezas = PiezaInventario::where('articulo_catalogo_id', $partida->articulo_catalogo_id)
                    ->where('estado', 'disponible')
                    ->lockForUpdate()
                    ->limit((int) $partida->cantidad)
                    ->get();

                if ($piezas->count() < (int) $partida->cantidad) {
                    throw new RuntimeException('Existencias insuficientes.');
                }

                foreach ($piezas as $pieza) {
                    $pieza->update([
                        'estado' => 'reservada',
                        'cotizacion_id' => $cotizacion->id,
                    ]);
                }
            }

            $cotizacion->update([
                'estado' => 'aceptada',
                'aceptada_en' => now(),
                'vence_en' => now()->addDays(5),
            ]);
        });
    }

    public function liberar(Cotizacion $cotizacion, bool $soloSiVencida = false): bool
    {
        return DB::transaction(function () use ($cotizacion, $soloSiVencida): bool {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            if ($cotizacion->estado !== 'aceptada') {
                return false;
            }
            if ($soloSiVencida && ($cotizacion->vence_en === null || $cotizacion->vence_en->isFuture())) {
                return false;
            }

            PiezaInventario::where('cotizacion_id', $cotizacion->id)
                ->where('estado', 'reservada')
                ->update([
                    'estado' => 'disponible',
                    'cotizacion_id' => null,
                ]);

            $cotizacion->update([
                'estado' => 'cancelada',
            ]);

            return true;
        });
    }
}
