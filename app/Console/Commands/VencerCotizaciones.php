<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Console\Command;

class VencerCotizaciones extends Command
{
    private const TAMANO_BLOQUE = 100;

    protected $signature = 'cotizaciones:vencer';

    protected $aliases = [
        'cotizaciones:vencer',
    ];

    protected $description = 'Vence cotizaciones pendientes y libera reservas aceptadas que vencieron';

    public function handle(): int
    {
        $pendingCount = Cotizacion::where('estado', Cotizacion::ESTADO_PENDIENTE)->whereNotNull('vence_en')->where('vence_en', '<=', now())->update([
            'estado' => Cotizacion::ESTADO_VENCIDA,
        ]);
        $reservasLiberadas = 0;
        Cotizacion::where('estado', Cotizacion::ESTADO_ACEPTADA)
            ->where(function ($consulta) {
                $consulta->where('entrega_limite_en', '<=', now())
                    ->orWhere(function ($consulta) {
                        $consulta->whereNull('entrega_limite_en')
                            ->where('vence_en', '<=', now());
                    });
            })
            ->chunkById(self::TAMANO_BLOQUE, function ($cotizaciones) use (&$reservasLiberadas): void {
                foreach ($cotizaciones as $cotizacion) {
                    if (app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true)) {
                        $reservasLiberadas++;
                    }
                }
            });

        $this->info("Cotizaciones vencidas: {$pendingCount}. Reservas liberadas: {$reservasLiberadas}.");

        return self::SUCCESS;
    }
}
