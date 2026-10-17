<?php

use App\Services\ServicioAuditoriaDominio;
use App\Support\ReglasIntegridadDominio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $motor = DB::connection()->getDriverName();
        $version = $motor === 'mysql' ? DB::selectOne('SELECT VERSION() AS version')->version : null;
        ReglasIntegridadDominio::validarMotor($motor, $version);
        $incompatibilidades = app(ServicioAuditoriaDominio::class)->incompatibilidades();
        if ($incompatibilidades !== []) {
            throw new RuntimeException(
                'Existen datos incompatibles en '.implode(', ', array_keys($incompatibilidades))
                .'. Ejecute php artisan dominio:auditar y revise los datos antes de migrar. No se modificó el esquema.'
            );
        }

        $creadas = [];
        try {
            foreach (ReglasIntegridadDominio::protecciones($motor) as $proteccion) {
                DB::unprepared($proteccion['crear']);
                $creadas[] = $proteccion;
            }
        } catch (Throwable $error) {
            // El DDL de MySQL confirma implícitamente: retirar únicamente los
            // objetos que alcanzó a crear esta ejecución, conservando los ajenos.
            foreach (array_reverse($creadas) as $proteccion) {
                DB::unprepared($proteccion['eliminar']);
            }
            throw $error;
        }
    }

    public function down(): void
    {
        $motor = DB::connection()->getDriverName();
        foreach (array_reverse(ReglasIntegridadDominio::protecciones($motor)) as $proteccion) {
            DB::unprepared($proteccion['eliminar']);
        }
    }
};
