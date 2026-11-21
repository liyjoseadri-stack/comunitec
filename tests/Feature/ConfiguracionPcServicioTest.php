<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\ServicioConfiguracionPc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConfiguracionPcServicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_una_plantilla_con_componentes_clasificados(): void
    {
        $producto = $this->producto('Placa B650', 'MB-01', Producto::TIPO_COMPONENTE_PLACA_MADRE);

        $plantilla = app(ServicioConfiguracionPc::class)->crearPlantilla([
            'nombre' => 'PC Oficina',
            'descripcion' => 'Configuración de oficina',
            'componentes' => [[
                'producto_id' => $producto->id,
                'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
                'cantidad' => 1,
            ]],
        ]);

        $this->assertSame('PC Oficina', $plantilla->nombre);
        $this->assertDatabaseHas('componentes_pc_armada', [
            'pc_armada_id' => $plantilla->id,
            'producto_id' => $producto->id,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
            'cantidad' => 1,
        ]);
    }

    public function test_rechaza_producto_inactivo_tipo_incorrecto_y_cantidad_invalida(): void
    {
        $producto = $this->producto('Placa B650', 'MB-02', Producto::TIPO_COMPONENTE_PLACA_MADRE);
        $servicio = app(ServicioConfiguracionPc::class);

        $this->expectException(ValidationException::class);
        $servicio->crearPlantilla([
            'nombre' => 'PC inválida',
            'componentes' => [[
                'producto_id' => $producto->id,
                'tipo_componente_pc' => Producto::TIPO_COMPONENTE_MEMORIA_RAM,
                'cantidad' => 0,
            ]],
        ]);
    }

    public function test_guarda_configuracion_privada_y_partidas_con_snapshot_de_componente(): void
    {
        $producto = $this->producto('Memoria RAM 16 GB', 'RAM-16', Producto::TIPO_COMPONENTE_MEMORIA_RAM);
        $cotizacion = $this->cotizacion();

        $configuracion = app(ServicioConfiguracionPc::class)->crearConfiguracionEnCotizacion(
            $cotizacion,
            [
                'nombre' => 'PC personalizada del cliente',
                'componentes' => [[
                    'producto_id' => $producto->id,
                    'tipo_componente_pc' => Producto::TIPO_COMPONENTE_MEMORIA_RAM,
                    'cantidad' => 2,
                    'porcentaje_descuento' => 0,
                ]],
            ]
        );

        $partida = $configuracion->partidas()->sole();
        $this->assertNull($configuracion->pc_armada_id);
        $this->assertSame('PC personalizada del cliente', $configuracion->nombre);
        $this->assertSame(Producto::TIPO_COMPONENTE_MEMORIA_RAM, $partida->tipo_componente_pc);
        $this->assertSame(2, (int) $partida->cantidad);
        $this->assertSame('Memoria RAM 16 GB', $partida->nombre_concepto);
    }

    public function test_una_plantilla_conserva_su_nombre_y_descripcion_y_no_se_edita_al_agregarla(): void
    {
        $producto = $this->producto('Procesador Ryzen 5', 'CPU-01', Producto::TIPO_COMPONENTE_PROCESADOR);
        $plantilla = app(ServicioConfiguracionPc::class)->crearPlantilla([
            'nombre' => 'PC Oficina Lista',
            'descripcion' => 'Equipo armado para oficina.',
            'componentes' => [[
                'producto_id' => $producto->id,
                'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
                'cantidad' => 1,
            ]],
        ]);

        $configuracion = app(ServicioConfiguracionPc::class)->crearConfiguracionEnCotizacion(
            $this->cotizacion(),
            [
                'nombre' => 'Nombre alterado',
                'descripcion' => 'Descripción alterada.',
                'componentes' => [],
            ],
            $plantilla
        );

        $this->assertSame('PC Oficina Lista', $configuracion->nombre);
        $this->assertSame('Equipo armado para oficina.', $configuracion->descripcion);
        $this->assertCount(1, $configuracion->partidas);
    }

    public function test_multiplica_componentes_precio_y_existencias_por_la_cantidad_de_pcs(): void
    {
        $producto = $this->producto('Procesador Ryzen 5', 'CPU-10', Producto::TIPO_COMPONENTE_PROCESADOR);
        $cotizacion = $this->cotizacion();

        $configuracion = app(ServicioConfiguracionPc::class)->crearConfiguracionEnCotizacion(
            $cotizacion,
            [
                'nombre' => 'PC para oficina',
                'cantidad' => 3,
                'componentes' => [[
                    'producto_id' => $producto->id,
                    'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
                    'cantidad' => 1,
                ]],
            ]
        );

        $this->assertSame(3, $configuracion->cantidad);
        $this->assertSame(3, (int) $configuracion->partidas->sole()->cantidad);
        $this->assertSame(3000.0, (float) $configuracion->partidas->sole()->subtotal);
    }

    private function producto(string $nombre, string $codigo, string $tipo): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'codigo' => $codigo,
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 5,
            'tipo_componente_pc' => $tipo,
        ]);
    }

    private function cotizacion(): Cotizacion
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente PC',
            'rfc' => 'CPC010101AA2',
            'correo' => 'cliente-pc-2@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);

        return Cotizacion::create([
            'folio' => 'COT-PC-02',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'total' => 0,
        ]);
    }
}
