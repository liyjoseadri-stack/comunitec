<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $tabla): void {
            $tabla->string('tipo_componente_pc', 50)->nullable()->index();
        });

        Schema::create('pc_armadas', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->string('nombre')->unique();
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
        });

        Schema::create('componentes_pc_armada', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->foreignId('pc_armada_id')->constrained('pc_armadas')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('tipo_componente_pc', 50);
            $tabla->unsignedInteger('cantidad');
            $tabla->unsignedInteger('orden')->default(0);
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
            $tabla->index(['pc_armada_id', 'tipo_componente_pc']);
            $tabla->unique(['pc_armada_id', 'producto_id']);
        });

        Schema::create('configuraciones_pc_cotizacion', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $tabla->foreignId('pc_armada_id')->nullable()->constrained('pc_armadas')->nullOnDelete();
            $tabla->string('nombre');
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
            $tabla->index(['cotizacion_id', 'creado_en']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE partidas_cotizacion ADD COLUMN configuracion_pc_cotizacion_id INTEGER NULL');
            DB::statement('ALTER TABLE partidas_cotizacion ADD COLUMN tipo_componente_pc VARCHAR(50) NULL');
        } else {
            Schema::table('partidas_cotizacion', function (Blueprint $tabla): void {
                $tabla->foreignId('configuracion_pc_cotizacion_id')
                    ->nullable()
                    ->constrained('configuraciones_pc_cotizacion')
                    ->nullOnDelete();
                $tabla->string('tipo_componente_pc', 50)->nullable();
            });
        }

        Schema::table('partidas_venta', function (Blueprint $tabla): void {
            $tabla->string('nombre_pc')->nullable();
            $tabla->string('tipo_componente_pc', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partidas_venta', function (Blueprint $tabla): void {
            $tabla->dropColumn(['nombre_pc', 'tipo_componente_pc']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE partidas_cotizacion DROP COLUMN configuracion_pc_cotizacion_id');
            DB::statement('ALTER TABLE partidas_cotizacion DROP COLUMN tipo_componente_pc');
        } else {
            Schema::table('partidas_cotizacion', function (Blueprint $tabla): void {
                $tabla->dropConstrainedForeignId('configuracion_pc_cotizacion_id');
                $tabla->dropColumn('tipo_componente_pc');
            });
        }

        Schema::dropIfExists('configuraciones_pc_cotizacion');
        Schema::dropIfExists('componentes_pc_armada');
        Schema::dropIfExists('pc_armadas');

        Schema::table('productos', function (Blueprint $tabla): void {
            $tabla->dropIndex('productos_tipo_componente_pc_index');
            $tabla->dropColumn('tipo_componente_pc');
        });
    }
};
