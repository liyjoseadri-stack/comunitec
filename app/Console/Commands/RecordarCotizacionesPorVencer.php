<?php

namespace App\Console\Commands;

use App\Mail\CorreoRecordatorioCotizacion;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Support\PlazosHabiles;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class RecordarCotizacionesPorVencer extends Command
{
    private const TAMANO_BLOQUE = 100;

    protected $signature = 'cotizaciones:recordar-vencimiento';

    protected $description = 'Envía un recordatorio al cliente un día hábil antes de vencer su cotización pendiente';

    public function handle(): int
    {
        $enviados = 0;
        $incidencias = 0;
        $fechaObjetivo = PlazosHabiles::sumar(today(), 1);
        $inicio = $fechaObjetivo->copy()->startOfDay();
        $fin = $fechaObjetivo->copy()->addDay()->startOfDay();

        Cotizacion::query()
            ->with('cliente', 'partidas.producto', 'partidas.servicio', 'responsable')
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->where('vence_en', '>=', $inicio)
            ->where('vence_en', '<', $fin)
            ->chunkById(self::TAMANO_BLOQUE, function ($cotizaciones) use (&$enviados, &$incidencias, $inicio, $fin): void {
                foreach ($cotizaciones as $cotizacion) {
                    $envio = $this->reservar($cotizacion, $inicio, $fin);

                    if (! $envio) {
                        continue;
                    }

                    $this->enviar($cotizacion, $envio, $enviados, $incidencias);
                }
            });

        $this->info("Recordatorios enviados: {$enviados}. Con incidencias: {$incidencias}.");

        return self::SUCCESS;
    }

    private function reservar(Cotizacion $cotizacion, CarbonInterface $inicio, CarbonInterface $fin): ?EnvioCotizacion
    {
        return DB::transaction(function () use ($cotizacion, $inicio, $fin): ?EnvioCotizacion {
            $actual = Cotizacion::query()->lockForUpdate()->find($cotizacion->id);

            if (! $actual
                || $actual->estado !== Cotizacion::ESTADO_PENDIENTE
                || ! $actual->vence_en
                || $actual->vence_en->lt($inicio)
                || ! $actual->vence_en->lt($fin)) {
                return null;
            }

            $tieneRecordatorioLegado = $actual->enviosCorreo()
                ->where('tipo', EnvioCotizacion::TIPO_RECORDATORIO)
                ->whereNull('clave_idempotencia')
                ->exists();

            if ($tieneRecordatorioLegado) {
                return null;
            }

            $clave = "recordatorio-vencimiento:{$actual->id}:{$actual->vence_en->toDateString()}";
            $ahora = now();
            $insertado = DB::table('envios_correo_cotizacion')->insertOrIgnore([
                'cotizacion_id' => $actual->id,
                'usuario_id' => $actual->usuario_id,
                'destinatario' => $cotizacion->cliente->correo,
                'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
                'clave_idempotencia' => $clave,
                'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
                'mensaje' => 'Intento reservado antes de contactar al servicio de correo.',
                'intentado_en' => $ahora,
                'creado_en' => $ahora,
                'actualizado_en' => $ahora,
            ]);

            if ($insertado !== 1) {
                return null;
            }

            return EnvioCotizacion::query()->where('clave_idempotencia', $clave)->firstOrFail();
        });
    }

    private function enviar(Cotizacion $cotizacion, EnvioCotizacion $envio, int &$enviados, int &$incidencias): void
    {
        try {
            Mail::to($cotizacion->cliente->correo)
                ->send(new CorreoRecordatorioCotizacion($cotizacion));
        } catch (UnexpectedResponseException $excepcion) {
            report($excepcion);
            $envio->update([
                'resultado' => EnvioCotizacion::RESULTADO_FALLIDO,
                'mensaje' => 'El servicio de correo confirmó que no aceptó el recordatorio automático.',
            ]);
            $incidencias++;

            return;
        } catch (Throwable $excepcion) {
            // El transporte pudo haber recibido el mensaje. La reserva queda
            // pendiente para impedir un segundo envío automático.
            report($excepcion);
            try {
                $envio->update([
                    'mensaje' => 'No se pudo confirmar el envío del recordatorio automático; requiere revisión manual.',
                ]);
            } catch (Throwable $errorPersistencia) {
                report($errorPersistencia);
            }
            $incidencias++;

            return;
        }

        try {
            $envio->update([
                'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
                'mensaje' => 'Recordatorio automático aceptado por el servicio de correo.',
            ]);
            $enviados++;
        } catch (Throwable $excepcion) {
            // El correo pudo salir: conservar la reserva evita un duplicado automático.
            report($excepcion);
            $incidencias++;
        }
    }
}
