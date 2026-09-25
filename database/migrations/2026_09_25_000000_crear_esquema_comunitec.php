<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre');
            $tabla->string('correo')->unique();
            $tabla->timestamp('correo_verificado_en')->nullable();
            $tabla->string('contrasena');
            $tabla->string('rol')->default('comercial');
            $tabla->boolean('activo')->default(true);
            $tabla->string('token_recuerdo', 100)->nullable();
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
        });

        Schema::create('clientes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tipo');
            $tabla->string('nombre');
            $tabla->string('rfc')->unique();
            $tabla->string('correo');
            $tabla->string('telefono');
            $tabla->string('direccion');
            $tabla->string('codigo_postal', 10);
            $this->marcasTiempo($tabla);
        });

        Schema::create('categorias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre')->unique();
            $tabla->boolean('activo')->default(true);
            $this->marcasTiempo($tabla);
        });

        Schema::create('articulos_catalogo', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $tabla->string('tipo');
            $tabla->string('nombre');
            $tabla->string('codigo')->unique();
            $tabla->string('marca')->nullable();
            $tabla->string('modelo')->nullable();
            $tabla->string('unidad');
            $tabla->decimal('precio', 12, 2);
            $tabla->unsignedInteger('existencias')->default(0);
            $tabla->boolean('activo')->default(true);
            $this->marcasTiempo($tabla);
        });

        Schema::create('cotizaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('folio')->unique();
            $tabla->foreignId('cliente_id')->constrained('clientes');
            $tabla->foreignId('usuario_id')->constrained('usuarios');
            $tabla->string('area_solicitante')->nullable();
            $tabla->string('estado')->default('borrador');
            $tabla->timestamp('enviada_en')->nullable();
            $tabla->timestamp('aceptada_en')->nullable();
            $tabla->timestamp('vence_en')->nullable();
            $tabla->decimal('porcentaje_descuento', 5, 2)->default(0);
            $tabla->decimal('total', 12, 2)->default(0);
            $this->marcasTiempo($tabla);
        });

        Schema::create('partidas_cotizacion', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $tabla->foreignId('articulo_catalogo_id')->nullable()->constrained('articulos_catalogo')->nullOnDelete();
            $tabla->string('tipo');
            $tabla->string('descripcion');
            $tabla->decimal('cantidad', 10, 2);
            $tabla->decimal('precio_unitario', 12, 2);
            $tabla->decimal('subtotal', 12, 2);
            $this->marcasTiempo($tabla);
        });

        Schema::create('piezas_inventario', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('articulo_catalogo_id')->constrained('articulos_catalogo')->cascadeOnDelete();
            $tabla->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $tabla->string('numero_serie')->unique();
            $tabla->string('estado')->default('disponible');
            $this->marcasTiempo($tabla);
        });

        Schema::create('envios_correo_cotizacion', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $tabla->foreignId('usuario_id')->constrained('usuarios');
            $tabla->string('destinatario');
            $tabla->string('resultado');
            $tabla->string('mensaje')->nullable();
            $tabla->timestamp('intentado_en');
            $this->marcasTiempo($tabla);
            $tabla->index(['cotizacion_id', 'intentado_en']);
        });

        Schema::create('ventas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('folio')->unique();
            $tabla->foreignId('cotizacion_id')->unique()->constrained('cotizaciones');
            $tabla->foreignId('cliente_id')->constrained('clientes');
            $tabla->foreignId('usuario_id')->constrained('usuarios');
            $tabla->string('tipo_cliente');
            $tabla->string('nombre_cliente');
            $tabla->string('rfc_cliente');
            $tabla->string('correo_cliente');
            $tabla->string('telefono_cliente');
            $tabla->string('direccion_cliente');
            $tabla->string('codigo_postal_cliente', 10);
            $tabla->string('nombre_responsable');
            $tabla->string('correo_responsable');
            $tabla->timestamp('vendida_en');
            $tabla->string('metodo_pago');
            $tabla->string('detalle_metodo_pago')->nullable();
            $tabla->decimal('subtotal', 12, 2);
            $tabla->decimal('porcentaje_descuento', 5, 2);
            $tabla->decimal('total', 12, 2);
            $this->marcasTiempo($tabla);
        });

        Schema::create('partidas_venta', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $tabla->foreignId('partida_cotizacion_id')->unique()->constrained('partidas_cotizacion');
            $tabla->foreignId('articulo_catalogo_id')->nullable()->constrained('articulos_catalogo')->nullOnDelete();
            $tabla->string('tipo');
            $tabla->string('descripcion');
            $tabla->decimal('cantidad', 10, 2);
            $tabla->decimal('precio_unitario', 12, 2);
            $tabla->decimal('subtotal', 12, 2);
            $this->marcasTiempo($tabla);
        });

        Schema::table('piezas_inventario', function (Blueprint $tabla) {
            $tabla->foreignId('partida_venta_id')->nullable()->constrained('partidas_venta')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('piezas_inventario', fn (Blueprint $tabla) => $tabla->dropConstrainedForeignId('partida_venta_id'));
        Schema::dropIfExists('partidas_venta');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('envios_correo_cotizacion');
        Schema::dropIfExists('piezas_inventario');
        Schema::dropIfExists('partidas_cotizacion');
        Schema::dropIfExists('cotizaciones');
        Schema::dropIfExists('articulos_catalogo');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
    }

    private function marcasTiempo(Blueprint $tabla): void
    {
        $tabla->timestamp('creado_en')->nullable();
        $tabla->timestamp('actualizado_en')->nullable();
    }
};
