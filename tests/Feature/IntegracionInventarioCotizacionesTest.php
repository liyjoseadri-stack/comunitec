<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
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
        ArticuloCatalogo::create([
            'tipo' => 'producto',
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
            ->assertSee('AP-001 · Access Point · Redes', false);
    }

    public function test_la_partida_conserva_su_descripcion_y_precio_historicos(): void
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
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Switch original',
            'codigo' => 'SW-001',
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
            'tipo' => 'producto',
            'articulo_catalogo_id' => $articulo->id,
            'descripcion' => 'Switch administrable cotizado',
            'cantidad' => 1,
        ])->assertRedirect();

        $articulo->update(['nombre' => 'Switch actualizado', 'precio' => 5500]);

        $this->assertDatabaseHas('partidas_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'Switch administrable cotizado',
            'precio_unitario' => 5000,
            'subtotal' => 5000,
        ]);
    }
}
