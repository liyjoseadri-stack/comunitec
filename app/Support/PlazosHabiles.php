<?php

namespace App\Support;

use Carbon\CarbonInterface;

class PlazosHabiles
{
    public static function sumar(CarbonInterface $fecha, int $dias): CarbonInterface
    {
        return $fecha->copy()->addWeekdays($dias);
    }

    public static function diaHabilAnterior(CarbonInterface $fecha): CarbonInterface
    {
        return $fecha->copy()->subWeekday();
    }
}
