<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

class CalculosCotizacion
{
    public const REGLA_PRECISION = 'decimal:0,2';

    public static function tienePrecisionValida(mixed $valor): bool
    {
        return Validator::make(['valor' => $valor], [
            'valor' => [self::REGLA_PRECISION],
        ])->passes();
    }

    public static function descontar(float $importe, float $porcentaje): float
    {
        return round($importe * (1 - $porcentaje / 100), 2);
    }
}
