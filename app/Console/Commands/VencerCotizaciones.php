<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Console\Command;

class VencerCotizaciones extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Vence cotizaciones pendientes y libera reservas aceptadas que vencieron';

    public function handle(): int
    {
        $pendingCount = Cotizacion::where('status', 'pending')->whereNotNull('expires_at')->where('expires_at', '<=', now())->update([
            'status' => 'expired',
        ]);
        $acceptedQuotes = Cotizacion::where('status', 'accepted')->whereNotNull('expires_at')->where('expires_at', '<=', now())->get();

        $reservasLiberadas = 0;
        foreach ($acceptedQuotes as $quote) {
            if (app(ServicioInventarioCotizacion::class)->liberar($quote, soloSiVencida: true)) {
                $reservasLiberadas++;
            }
        }

        $this->info("Cotizaciones vencidas: {$pendingCount}. Reservas liberadas: {$reservasLiberadas}.");

        return self::SUCCESS;
    }
}
