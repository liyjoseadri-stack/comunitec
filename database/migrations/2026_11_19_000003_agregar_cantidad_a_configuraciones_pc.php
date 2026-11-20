<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_pc_cotizacion', function (Blueprint $tabla): void {
            $tabla->unsignedInteger('cantidad')->default(1)->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_pc_cotizacion', function (Blueprint $tabla): void {
            $tabla->dropColumn('cantidad');
        });
    }
};
