<?php

namespace App\Console\Commands;

use App\Mail\CorreoRecordatorioCotizacion;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Support\PlazosHabiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RecordarCotizacionesPorVencer extends Command
{
    protected $signature = 'cotizaciones:recordar-vencimiento';

    protected $description = 'Envía un recordatorio al cliente un día hábil antes de vencer su cotización pendiente';

    public function handle(): int
    {
        $enviados = 0;
        $fallidos = 0;

        Cotizacion::query()
            ->with('cliente', 'partidas.producto', 'partidas.servicio', 'responsable')
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->whereNotNull('vence_en')
            ->whereDoesntHave('enviosCorreo', fn ($consulta) => $consulta
                ->where('tipo', EnvioCotizacion::TIPO_RECORDATORIO)
                ->where('resultado', EnvioCotizacion::RESULTADO_ACEPTADO))
            ->get()
            ->filter(fn (Cotizacion $cotizacion): bool => PlazosHabiles::diaHabilAnterior($cotizacion->vence_en)
                ->isSameDay(today()))
            ->each(function (Cotizacion $cotizacion) use (&$enviados, &$fallidos): void {
                try {
                    Mail::to($cotizacion->cliente->correo)
                        ->send(new CorreoRecordatorioCotizacion($cotizacion));
                    $this->registrar($cotizacion, EnvioCotizacion::RESULTADO_ACEPTADO, 'Recordatorio automático aceptado por el servicio de correo.');
                    $enviados++;
                } catch (Throwable $excepcion) {
                    report($excepcion);
                    $this->registrar($cotizacion, EnvioCotizacion::RESULTADO_FALLIDO, 'No se pudo enviar el recordatorio automático.');
                    $fallidos++;
                }
            });

        $this->info("Recordatorios enviados: {$enviados}. Fallidos: {$fallidos}.");

        return self::SUCCESS;
    }

    private function registrar(Cotizacion $cotizacion, string $resultado, string $mensaje): void
    {
        $cotizacion->enviosCorreo()->create([
            'usuario_id' => $cotizacion->usuario_id,
            'destinatario' => $cotizacion->cliente->correo,
            'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
            'resultado' => $resultado,
            'mensaje' => $mensaje,
            'intentado_en' => now(),
        ]);
    }
}
