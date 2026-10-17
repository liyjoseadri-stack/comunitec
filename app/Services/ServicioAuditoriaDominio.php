<?php

namespace App\Services;

use App\Support\ReglasIntegridadDominio;
use Illuminate\Support\Facades\DB;

class ServicioAuditoriaDominio
{
    public function incompatibilidades(): array
    {
        $porTabla = [];
        foreach (ReglasIntegridadDominio::reglas(DB::connection()->getDriverName()) as $regla) {
            $porTabla[$regla['tabla']][] = 'NOT COALESCE(('.$regla['expresion'].'), 0)';
        }
        $resultado = [];
        foreach ($porTabla as $tabla => $condiciones) {
            $consulta = DB::table($tabla)->whereRaw('('.implode(' OR ', $condiciones).')');
            $cantidad = (clone $consulta)->count();
            if ($cantidad > 0) {
                $resultado[$tabla] = [
                    'cantidad' => $cantidad,
                    'ids' => $consulta->orderBy('id')->limit(20)->pluck('id')->all(),
                ];
            }
        }

        return $resultado;
    }
}
