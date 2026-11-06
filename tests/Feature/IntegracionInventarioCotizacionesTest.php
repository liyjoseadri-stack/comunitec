<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegracionInventarioCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_selector_muestra_codigo_nombre_y_categoria_del_inventario(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente integración',
            'rfc' => 'CIN010101AA1',
            'correo' => 'integracion@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $categoria = Categoria::create(['nombre' => 'Redes', 'activo' => true]);
        Producto::create([

            'nombre' => 'Access Point',
            'codigo' => 'AP-001',
            'categoria_id' => $categoria->id,
            'unidad' => 'pieza',
            'precio' => 3500,
            'existencias' => 5,
            'activo' => true,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-INTEGRACION-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'porcentaje_descuento' => 0,
            'total' => 0,
        ]);

        $this->actingAs($usuario)
            ->get(route('cotizaciones.detalle', $cotizacion))
            ->assertOk()
            ->assertSee('AP-001 · Access Point · Redes', false)
            ->assertSee('Agregar producto o servicio')
            ->assertSee('Conceptos de la cotización')
            ->assertSee('formulario-administrativo formulario-concepto', false)
            ->assertDontSee('>Partidas<', false)
            ->assertDontSee('Agregar partida');
    }

    public function test_la_partida_toma_descripcion_y_precio_del_catalogo(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente histórico',
            'rfc' => 'CHI010101AA1',
            'correo' => 'historico@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $articulo = Producto::create([

            'nombre' => 'Switch original',
            'codigo' => 'SW-001',
            'descripcion' => 'Descripción oficial del switch',
            'unidad' => 'pieza',
            'precio' => 5000,
            'existencias' => 5,
            'activo' => true,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-INTEGRACION-2',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'porcentaje_descuento' => 0,
            'total' => 0,
        ]);

        $this->actingAs($usuario)->post(route('cotizaciones.partidas.guardar', $cotizacion), [

            'producto_id' => $articulo->id,
            'descripcion' => 'Descripción manipulada',
            'cantidad' => 1,
        ])->assertRedirect();

        $articulo->update(['nombre' => 'Switch actualizado', 'precio' => 5500]);

        $this->assertDatabaseHas('partidas_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'Descripción oficial del switch',
            'precio_unitario' => 5000,
            'subtotal' => 5000,
        ]);
    }

    public function test_producto_y_servicio_no_requieren_descripcion_ni_precio_manual(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente captura simple',
            'rfc' => 'CCS010101AA1',
            'correo' => 'captura@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Router empresarial',
            'codigo' => 'RTR-001',
            'descripcion' => 'Router administrable del catálogo',
            'unidad' => 'pieza',
            'precio' => 2400,
            'existencias' => 3,
            'activo' => true,
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Configuración de red',
            'codigo' => 'SRV-001',
            'descripcion' => null,
            'unidad' => 'servicio',
            'precio' => 800,
            'activo' => true,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-CAPTURA-SIMPLE',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'porcentaje_descuento' => 0,
            'total' => 0,
        ]);

        $this->actingAs($usuario)
            ->post(route('cotizaciones.partidas.guardar', $cotizacion), [
                'tipo' => 'producto',
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'porcentaje_descuento' => 0,
            ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->actingAs($usuario)
            ->post(route('cotizaciones.partidas.guardar', $cotizacion), [
                'tipo' => 'servicio',
                'servicio_id' => $servicio->id,
                'cantidad' => 1,
                'porcentaje_descuento' => 0,
            ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('partidas_cotizacion', [
            'producto_id' => $producto->id,
            'descripcion' => 'Router administrable del catálogo',
            'precio_unitario' => 2400,
        ]);
        $this->assertDatabaseHas('partidas_cotizacion', [
            'servicio_id' => $servicio->id,
            'descripcion' => 'Configuración de red',
            'precio_unitario' => 800,
        ]);
    }

    public function test_cantidades_y_descuentos_fraccionarios_no_se_aceptan(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral', 'nombre' => 'Cliente enteros', 'rfc' => 'CEN010101AA1',
            'correo' => 'enteros@cliente.test', 'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba', 'codigo_postal' => '29000',
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Mantenimiento', 'codigo' => 'SRV-ENTERO', 'unidad' => 'servicio',
            'precio' => 400, 'activo' => true,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ENTEROS', 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id,
            'estado' => 'borrador', 'porcentaje_descuento' => 0, 'total' => 0,
        ]);

        $this->actingAs($usuario)->post(route('cotizaciones.partidas.guardar', $cotizacion), [
            'tipo' => 'servicio', 'servicio_id' => $servicio->id,
            'cantidad' => 0.99, 'porcentaje_descuento' => 0.03,
        ])->assertSessionHasErrors(['cantidad', 'porcentaje_descuento']);

        $this->assertDatabaseCount('partidas_cotizacion', 0);
    }
}
