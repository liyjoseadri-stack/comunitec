<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservasInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_aceptar_reserva_la_suma_de_partidas_del_mismo_producto(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(5, [2, 1]);

        $this->actingAs($usuario)
            ->post("/cotizaciones/{$cotizacion->id}/aceptar")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $producto->fresh()->existencias);
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
    }

    public function test_stock_insuficiente_no_deja_descuentos_parciales(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(2, [1, 2]);

        $this->actingAs($usuario)
            ->post("/cotizaciones/{$cotizacion->id}/aceptar")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(2, $producto->fresh()->existencias);
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
    }

    public function test_cancelar_devuelve_el_stock_reservado_exactamente_una_vez(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(5, [2]);
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar");
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame(3, $producto->fresh()->existencias);

        $this->post("/cotizaciones/{$cotizacion->id}/cancelar")->assertRedirect();
        $this->post("/cotizaciones/{$cotizacion->id}/cancelar")->assertStatus(422);

        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertSame('cancelada', $cotizacion->fresh()->estado);
    }

    public function test_vencer_una_aceptada_devuelve_el_stock(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(5, [2]);
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar");
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame(3, $producto->fresh()->existencias);
        $cotizacion->update(['vence_en' => now()->subMinute()]);

        $this->artisan('cotizaciones:vencer')->assertSuccessful();

        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertSame('cancelada', $cotizacion->fresh()->estado);
    }

    public function test_editar_una_aceptada_devuelve_stock_y_exige_nueva_aceptacion(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(5, [2]);
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar");

        $this->put("/cotizaciones/{$cotizacion->id}", [
            'cliente_id' => $cotizacion->cliente_id,
            'area_solicitante' => 'Infraestructura',
            'porcentaje_descuento' => 0,
        ])->assertRedirect();

        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('Infraestructura', $cotizacion->fresh()->area_solicitante);
    }

    public function test_editar_una_partida_aceptada_devuelve_la_reserva_original(): void
    {
        [$usuario, $cotizacion, $producto] = $this->prepararCotizacion(5, [2]);
        $partida = $cotizacion->partidas()->firstOrFail();
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar");

        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $producto->id,
            'descripcion' => 'Memoria RAM actualizada',
            'cantidad' => 1,
            'precio_unitario' => 800,
        ])->assertRedirect();

        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('1.00', $partida->fresh()->cantidad);
    }

    private function prepararCotizacion(int $existencias, array $cantidades): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente reservas',
            'rfc' => 'RES010101AA1',
            'correo' => 'reservas@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Memoria RAM',
            'codigo' => 'RAM-RESERVA',
            'unidad' => 'pieza',
            'precio' => 800,
            'existencias' => $existencias,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-RESERVA-AGREGADA',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'vence_en' => now()->addDays(15),
            'total' => array_sum($cantidades) * 800,
        ]);

        foreach ($cantidades as $indice => $cantidad) {
            $cotizacion->partidas()->create([
                'articulo_catalogo_id' => $producto->id,
                'tipo' => 'producto',
                'descripcion' => 'Memoria RAM partida '.($indice + 1),
                'cantidad' => $cantidad,
                'precio_unitario' => 800,
                'subtotal' => $cantidad * 800,
            ]);
        }

        return [$usuario, $cotizacion, $producto];
    }
}
