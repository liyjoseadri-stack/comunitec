<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_existencias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabla->unsignedInteger('existencias_anteriores');
            $tabla->unsignedInteger('existencias_nuevas');
            $tabla->string('motivo', 500);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->index(['producto_id', 'creado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_existencias');
    }
};
