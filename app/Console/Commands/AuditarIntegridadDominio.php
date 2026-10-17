<?php

namespace App\Console\Commands;

use App\Services\ServicioAuditoriaDominio;
use Illuminate\Console\Command;

class AuditarIntegridadDominio extends Command
{
    protected $signature = 'dominio:auditar';

    protected $description = 'Audita estados, tipos y relaciones sin modificar datos.';

    public function handle(ServicioAuditoriaDominio $auditoria): int
    {
        $incompatibilidades = $auditoria->incompatibilidades();
        if ($incompatibilidades === []) {
            $this->info('Sin incompatibilidades de dominio. Auditoría de solo lectura.');

            return self::SUCCESS;
        }
        foreach ($incompatibilidades as $tabla => $datos) {
            $this->error($tabla.': '.$datos['cantidad'].' registro(s) incompatible(s); IDs: '.implode(', ', $datos['ids']));
            if ($datos['cantidad'] > count($datos['ids'])) {
                $this->line('Se muestran los primeros 20 identificadores de esta tabla.');
            }
        }
        $this->warn('No se corrigieron ni borraron datos. Revisar las incompatibilidades antes de migrar.');

        return self::FAILURE;
    }
}
