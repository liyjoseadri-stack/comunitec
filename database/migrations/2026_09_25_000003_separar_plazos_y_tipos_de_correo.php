<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->timestamp('entrega_limite_en')->nullable()->after('vence_en');
            $tabla->index(['estado', 'entrega_limite_en']);
        });

        Schema::table('envios_correo_cotizacion', function (Blueprint $tabla) {
            $tabla->string('tipo')->default('cotizacion')->after('destinatario');
            $tabla->index(['cotizacion_id', 'tipo', 'resultado'], 'envios_cotizacion_tipo_resultado_indice');
        });

        DB::table('cotizaciones')
            ->where('estado', 'aceptada')
            ->whereNull('entrega_limite_en')
            ->update(['entrega_limite_en' => DB::raw('vence_en')]);
    }

    public function down(): void
    {
        Schema::table('envios_correo_cotizacion', function (Blueprint $tabla) {
            $tabla->dropIndex('envios_cotizacion_tipo_resultado_indice');
            $tabla->dropColumn('tipo');
        });

        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->dropIndex(['estado', 'entrega_limite_en']);
            $tabla->dropColumn('entrega_limite_en');
        });
    }
};
