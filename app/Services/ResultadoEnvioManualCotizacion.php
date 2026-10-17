<?php

namespace App\Services;

final readonly class ResultadoEnvioManualCotizacion
{
    public const ACEPTADO = 'aceptado';

    public const ESTADO_CAMBIO = 'estado_cambio';

    public const FALLIDO = 'fallido';

    public const INCIERTO = 'incierto';

    public function __construct(public string $estado) {}
}
