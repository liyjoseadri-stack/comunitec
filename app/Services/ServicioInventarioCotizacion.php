<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ServicioInventarioCotizacion
{
    public function reservar(Cotizacion $quote): void
    {
        DB::transaction(function () use ($quote): void {
            $quote = Cotizacion::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($quote->status !== 'pending') {
                throw ValidationException::withMessages([
                    'cotizacion' => 'Solo se puede aceptar una cotización pendiente.',
                ]);
            }
            if ($quote->expires_at !== null && $quote->expires_at->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'cotizacion' => 'La vigencia de la cotización terminó; no puede aceptarse.',
                ]);
            }
            foreach ($quote->lines()->where('type', 'product')->get() as $line) {
                $units = PiezaInventario::where('catalog_item_id', $line->catalog_item_id)
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->limit((int) $line->quantity)
                    ->get();

                if ($units->count() < (int) $line->quantity) {
                    throw new RuntimeException('Existencias insuficientes.');
                }

                foreach ($units as $unit) {
                    $unit->update([
                        'status' => 'reserved',
                        'quote_id' => $quote->id,
                    ]);
                }
            }

            $quote->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'expires_at' => now()->addDays(5),
            ]);
        });
    }

    public function liberar(Cotizacion $quote, bool $soloSiVencida = false): bool
    {
        return DB::transaction(function () use ($quote, $soloSiVencida): bool {
            $quote = Cotizacion::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($quote->status !== 'accepted') {
                return false;
            }
            if ($soloSiVencida && ($quote->expires_at === null || $quote->expires_at->isFuture())) {
                return false;
            }

            PiezaInventario::where('quote_id', $quote->id)
                ->where('status', 'reserved')
                ->update([
                    'status' => 'available',
                    'quote_id' => null,
                ]);

            $quote->update([
                'status' => 'cancelled',
            ]);

            return true;
        });
    }
}
