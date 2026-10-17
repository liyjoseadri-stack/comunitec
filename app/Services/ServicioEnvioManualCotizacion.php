<?php

namespace App\Services;

use App\Mail\CorreoCotizacion;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Support\PlazosHabiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class ServicioEnvioManualCotizacion
{
    public function __construct(private readonly ServicioIntegridadCotizacion $integridad) {}

    public function procesar(Cotizacion $cotizacion, string $estadoEsperado, string $token, int $usuarioId): ResultadoEnvioManualCotizacion
    {
        $clave = "correo-manual:{$cotizacion->id}:{$token}";
        [$envio, $esNuevo] = $this->reservar($cotizacion->id, $estadoEsperado, $clave, $usuarioId);

        if (! $esNuevo) {
            return new ResultadoEnvioManualCotizacion(match ($envio->resultado) {
                EnvioCotizacion::RESULTADO_ACEPTADO => ResultadoEnvioManualCotizacion::ACEPTADO,
                EnvioCotizacion::RESULTADO_FALLIDO => ResultadoEnvioManualCotizacion::FALLIDO,
                default => ResultadoEnvioManualCotizacion::INCIERTO,
            });
        }

        $actual = Cotizacion::query()
            ->with('cliente', 'partidas.producto', 'partidas.servicio', 'responsable')
            ->findOrFail($cotizacion->id);

        try {
            Mail::to($envio->destinatario)->send(new CorreoCotizacion($actual));
        } catch (UnexpectedResponseException $excepcion) {
            report($excepcion);

            try {
                $this->marcarFallido($envio->id);
            } catch (Throwable $errorPersistencia) {
                report($errorPersistencia);

                return new ResultadoEnvioManualCotizacion(ResultadoEnvioManualCotizacion::INCIERTO);
            }

            return new ResultadoEnvioManualCotizacion(ResultadoEnvioManualCotizacion::FALLIDO);
        } catch (Throwable $excepcion) {
            // El transporte pudo haber recibido el mensaje. La reserva queda pendiente para impedir duplicados.
            report($excepcion);

            return new ResultadoEnvioManualCotizacion(ResultadoEnvioManualCotizacion::INCIERTO);
        }

        try {
            return $this->confirmar($envio->id, $cotizacion->id, $estadoEsperado);
        } catch (Throwable $excepcion) {
            // El SMTP ya respondió; conservar pendiente evita repetir un correo posiblemente entregado.
            report($excepcion);

            return new ResultadoEnvioManualCotizacion(ResultadoEnvioManualCotizacion::INCIERTO);
        }
    }

    /** @return array{EnvioCotizacion, bool} */
    private function reservar(int $cotizacionId, string $estadoEsperado, string $clave, int $usuarioId): array
    {
        return DB::transaction(function () use ($cotizacionId, $estadoEsperado, $clave, $usuarioId): array {
            $actual = Cotizacion::query()->lockForUpdate()->findOrFail($cotizacionId);
            $existente = EnvioCotizacion::query()->where('clave_idempotencia', $clave)->first();

            if ($existente) {
                return [$existente, false];
            }

            abort_unless($actual->estado === $estadoEsperado, 422, 'El estado de la cotización no permite este envío.');
            $this->integridad->validarParaAvanzar($actual);
            $destinatario = $actual->cliente()->value('correo');

            return [EnvioCotizacion::query()->create([
                'cotizacion_id' => $actual->id,
                'usuario_id' => $usuarioId,
                'destinatario' => $destinatario,
                'tipo' => EnvioCotizacion::TIPO_COTIZACION,
                'clave_idempotencia' => $clave,
                'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
                'mensaje' => 'Intento manual reservado antes de contactar al servicio de correo.',
                'intentado_en' => now(),
            ]), true];
        });
    }

    private function marcarFallido(int $envioId): void
    {
        DB::transaction(function () use ($envioId): void {
            EnvioCotizacion::query()
                ->whereKey($envioId)
                ->where('resultado', EnvioCotizacion::RESULTADO_PENDIENTE)
                ->update([
                    'resultado' => EnvioCotizacion::RESULTADO_FALLIDO,
                    'mensaje' => 'El servicio de correo confirmó que no aceptó el mensaje.',
                ]);
        });
    }

    private function confirmar(int $envioId, int $cotizacionId, string $estadoEsperado): ResultadoEnvioManualCotizacion
    {
        return DB::transaction(function () use ($envioId, $cotizacionId, $estadoEsperado): ResultadoEnvioManualCotizacion {
            $actual = Cotizacion::query()->lockForUpdate()->findOrFail($cotizacionId);
            $envio = EnvioCotizacion::query()->lockForUpdate()->findOrFail($envioId);

            if ($envio->resultado !== EnvioCotizacion::RESULTADO_PENDIENTE) {
                return new ResultadoEnvioManualCotizacion(
                    $envio->resultado === EnvioCotizacion::RESULTADO_ACEPTADO
                        ? ResultadoEnvioManualCotizacion::ACEPTADO
                        : ResultadoEnvioManualCotizacion::FALLIDO
                );
            }

            $estadoCambio = $actual->estado !== $estadoEsperado;
            if (! $estadoCambio && $estadoEsperado === Cotizacion::ESTADO_BORRADOR) {
                $actual->update([
                    'estado' => Cotizacion::ESTADO_PENDIENTE,
                    'enviada_en' => now(),
                    'vence_en' => PlazosHabiles::sumar(now(), 15),
                ]);
            }

            $envio->update([
                'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
                'mensaje' => $estadoCambio
                    ? 'El servicio de correo aceptó el mensaje, pero el estado cambió durante el envío y no fue sobrescrito.'
                    : 'El servicio de correo aceptó el mensaje para su envío.',
            ]);

            return new ResultadoEnvioManualCotizacion(
                $estadoCambio ? ResultadoEnvioManualCotizacion::ESTADO_CAMBIO : ResultadoEnvioManualCotizacion::ACEPTADO
            );
        });
    }
}
