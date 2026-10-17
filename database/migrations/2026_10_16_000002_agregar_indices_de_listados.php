<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $tabla): void {
            $tabla->index(['creado_en', 'id'], 'cotizaciones_creadas_indice');
            $tabla->index(['estado', 'creado_en', 'id'], 'cotizaciones_estado_creadas_indice');
        });

        Schema::table('ventas', function (Blueprint $tabla): void {
            $tabla->index(['vendida_en', 'id'], 'ventas_vendidas_indice');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $tabla): void {
            $tabla->dropIndex('ventas_vendidas_indice');
        });

        Schema::table('cotizaciones', function (Blueprint $tabla): void {
            $tabla->dropIndex('cotizaciones_estado_creadas_indice');
            $tabla->dropIndex('cotizaciones_creadas_indice');
        });
    }
};
