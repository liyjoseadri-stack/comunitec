<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Console\Command;

class VencerCotizaciones extends Command
{
    protected $signature = 'cotizaciones:vencer';

    protected $aliases = [
        'cotizaciones:vencer',
    ];

    protected $description = 'Vence cotizaciones pendientes y libera reservas aceptadas que vencieron';

    public function handle(): int
    {
        $pendingCount = Cotizacion::where('estado', 'pendiente')->whereNotNull('vence_en')->where('vence_en', '<=', now())->update([
            'estado' => 'vencida',
        ]);
        $cotizacionesAceptadas = Cotizacion::where('estado', 'aceptada')->whereNotNull('vence_en')->where('vence_en', '<=', now())->get();

        $reservasLiberadas = 0;
        foreach ($cotizacionesAceptadas as $cotizacion) {
            if (app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true)) {
                $reservasLiberadas++;
            }
        }

        $this->info("Cotizaciones vencidas: {$pendingCount}. Reservas liberadas: {$reservasLiberadas}.");

        return self::SUCCESS;
    }
}
