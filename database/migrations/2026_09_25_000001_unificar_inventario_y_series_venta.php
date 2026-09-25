<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos_catalogo', function (Blueprint $tabla) {
            $tabla->text('descripcion')->nullable()->after('nombre');
            $tabla->boolean('requiere_numero_serie')->default(false)->after('existencias');
            $tabla->index(['tipo', 'activo']);
            $tabla->index(['categoria_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::table('articulos_catalogo', function (Blueprint $tabla) {
            $tabla->dropIndex(['tipo', 'activo']);
            $tabla->dropIndex(['categoria_id', 'activo']);
            $tabla->dropColumn(['descripcion', 'requiere_numero_serie']);
        });
    }
};
