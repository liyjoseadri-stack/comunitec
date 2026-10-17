<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_correo_cotizacion', function (Blueprint $tabla) {
            $tabla->string('clave_idempotencia', 191)->nullable()->after('tipo');
            $tabla->unique('clave_idempotencia', 'envios_correo_clave_idempotencia_unica');
        });

        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->index(['estado', 'vence_en', 'id'], 'cotizaciones_recordatorio_vencimiento_indice');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->dropIndex('cotizaciones_recordatorio_vencimiento_indice');
        });

        Schema::table('envios_correo_cotizacion', function (Blueprint $tabla) {
            $tabla->dropUnique('envios_correo_clave_idempotencia_unica');
            $tabla->dropColumn('clave_idempotencia');
        });
    }
};
