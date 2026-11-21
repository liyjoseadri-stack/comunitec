<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PcArmada;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PcArmadasEsquemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_esquema_agrega_tipos_y_tablas_de_pc(): void
    {
        $this->assertTrue(Schema::hasColumn('productos', 'tipo_componente_pc'));
        $this->assertTrue(Schema::hasColumns('pc_armadas', [
            'nombre',
            'descripcion',
            'activo',
        ]));
        $this->assertTrue(Schema::hasColumns('componentes_pc_armada', [
            'pc_armada_id',
            'producto_id',
            'tipo_componente_pc',
            'cantidad',
        ]));
        $this->assertTrue(Schema::hasColumns('configuraciones_pc_cotizacion', [
            'cotizacion_id',
            'pc_armada_id',
            'nombre',
            'descripcion',
            'cantidad',
        ]));
        $this->assertTrue(Schema::hasColumns('partidas_cotizacion', [
            'configuracion_pc_cotizacion_id',
            'tipo_componente_pc',
        ]));
        $this->assertTrue(Schema::hasColumns('partidas_venta', [
            'nombre_pc',
            'tipo_componente_pc',
        ]));
    }

    public function test_los_modelos_navegan_plantilla_componentes_y_configuracion_privada(): void
    {
        $producto = Producto::create([
            'nombre' => 'Procesador de prueba',
            'codigo' => 'CPU-PC-01',
            'unidad' => 'pieza',
            'precio' => 2500,
            'existencias' => 3,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
        ]);
        $plantilla = PcArmada::create([
            'nombre' => 'PC Oficina',
            'descripcion' => 'Configuración de oficina',
        ]);
        $componente = $plantilla->componentes()->create([
            'producto_id' => $producto->id,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
            'cantidad' => 1,
        ]);
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente PC',
            'rfc' => 'CPC010101AA1',
            'correo' => 'cliente-pc@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PC-01',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'total' => 2500,
        ]);
        $configuracion = $cotizacion->configuracionesPc()->create([
            'pc_armada_id' => $plantilla->id,
            'nombre' => 'PC Oficina para cliente',
        ]);

        $this->assertTrue($componente->producto->is($producto));
        $this->assertTrue($plantilla->componentes->contains($componente));
        $this->assertTrue($configuracion->cotizacion->is($cotizacion));
        $this->assertTrue($configuracion->plantilla->is($plantilla));
    }

    public function test_producto_expone_los_tipos_de_componente_en_espanol(): void
    {
        $this->assertContains(Producto::TIPO_COMPONENTE_PLACA_MADRE, Producto::tiposComponentesPc());
        $this->assertContains(Producto::TIPO_COMPONENTE_MEMORIA_RAM, Producto::tiposComponentesPc());
        $this->assertSame('Placa madre', Producto::etiquetaTipoComponentePc(Producto::TIPO_COMPONENTE_PLACA_MADRE));
    }
}
