<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_pc_cotizacion', function (Blueprint $tabla): void {
            $tabla->text('descripcion')->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_pc_cotizacion', function (Blueprint $tabla): void {
            $tabla->dropColumn('descripcion');
        });
    }
};
