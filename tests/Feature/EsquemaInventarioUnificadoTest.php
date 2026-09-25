<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaVenta;
use App\Models\PiezaInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EsquemaInventarioUnificadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_esquema_configura_descripcion_y_series_por_producto(): void
    {
        $this->assertTrue(Schema::hasColumns('productos', [
            'descripcion',
            'requiere_numero_serie',
        ]));

        $producto = Producto::create([

            'nombre' => 'Switch',
            'codigo' => 'SW-DEFAULT',
            'unidad' => 'pieza',
            'precio' => 1200,
            'existencias' => 2,
        ]);

        $this->assertFalse($producto->fresh()->requiere_numero_serie);
    }

    public function test_categoria_y_producto_tienen_relaciones_navegables(): void
    {
        $categoria = Categoria::create(['nombre' => 'Redes']);
        $producto = $categoria->productos()->create([

            'nombre' => 'Switch',
            'codigo' => 'SW-01',
            'descripcion' => 'Switch administrable de 24 puertos',
            'unidad' => 'pieza',
            'precio' => 1200,
            'existencias' => 2,
            'requiere_numero_serie' => true,
        ]);

        $this->assertTrue($producto->categoria->is($categoria));
        $this->assertTrue($categoria->productos->contains($producto));
        $this->assertTrue($producto->requiere_numero_serie);
    }

    public function test_producto_y_partida_conservan_acceso_a_una_serie_entregada(): void
    {
        [$producto, $partidaVenta] = $this->crearPartidaVendida();
        $serie = PiezaInventario::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'SERIE-HISTORICA-01',
            'estado' => 'entregada',
            'partida_venta_id' => $partidaVenta->id,
        ]);

        $this->assertTrue($producto->seriesVendidas->contains($serie));
        $this->assertTrue($partidaVenta->piezas->contains($serie));
    }

    private function crearPartidaVendida(): array
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente de prueba',
            'rfc' => 'XAXX010101000',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([

            'nombre' => 'Access Point',
            'codigo' => 'AP-01',
            'unidad' => 'pieza',
            'precio' => 1500,
            'existencias' => 0,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ESQUEMA-01',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'total' => 1500,
        ]);
        $partidaCotizacion = $cotizacion->partidas()->create([
            'producto_id' => $producto->id,

            'descripcion' => 'Access Point',
            'cantidad' => 1,
            'precio_unitario' => 1500,
            'subtotal' => 1500,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-ESQUEMA-01',
            'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'tipo_cliente' => $cliente->tipo,
            'nombre_cliente' => $cliente->nombre,
            'rfc_cliente' => $cliente->rfc,
            'correo_cliente' => $cliente->correo,
            'telefono_cliente' => $cliente->telefono,
            'direccion_cliente' => $cliente->direccion,
            'codigo_postal_cliente' => $cliente->codigo_postal,
            'nombre_responsable' => $usuario->nombre,
            'correo_responsable' => $usuario->correo,
            'vendida_en' => now(),
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'subtotal' => 1500,
            'porcentaje_descuento' => 0,
            'total' => 1500,
        ]);
        $partidaVenta = PartidaVenta::create([
            'venta_id' => $venta->id,
            'partida_cotizacion_id' => $partidaCotizacion->id,
            'producto_id' => $producto->id,

            'descripcion' => 'Access Point',
            'cantidad' => 1,
            'precio_unitario' => 1500,
            'subtotal' => 1500,
        ]);

        return [$producto, $partidaVenta];
    }
}
