<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->text('notas')->nullable()->after('area_solicitante');
        });

        Schema::table('partidas_cotizacion', function (Blueprint $tabla) {
            $tabla->decimal('porcentaje_descuento', 5, 2)
                ->default(0)
                ->after('precio_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('partidas_cotizacion', function (Blueprint $tabla) {
            $tabla->dropColumn('porcentaje_descuento');
        });

        Schema::table('cotizaciones', function (Blueprint $tabla) {
            $tabla->dropColumn('notas');
        });
    }
};
