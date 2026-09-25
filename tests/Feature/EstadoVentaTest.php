<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estado_anterior_se_migra_a_venta(): void
    {
        [$usuario, $cliente] = $this->datosBase();
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ESTADO-ANTERIOR',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'se_hizo_venta',
            'total' => 100,
        ]);

        $migracion = require database_path('migrations/2026_09_25_000002_actualizar_estado_venta_cotizaciones.php');
        $migracion->up();

        $this->assertSame(Cotizacion::ESTADO_VENTA, $cotizacion->fresh()->estado);
        $this->assertSame('Venta', $cotizacion->fresh()->etiquetaEstado());
    }

    public function test_convertir_una_cotizacion_cambia_su_estado_persistente_a_venta(): void
    {
        [$usuario, $cliente] = $this->datosBase();
        $producto = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Consumible',
            'codigo' => 'CON-ESTADO',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 1,
            'requiere_numero_serie' => false,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ESTADO-VENTA',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'vence_en' => now()->addDays(5),
            'total' => 100,
        ]);
        $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $producto->id,
            'tipo' => 'producto',
            'descripcion' => 'Consumible',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
            'metodo_pago' => Venta::METODO_EFECTIVO,
        ])->assertRedirect();

        $this->assertSame(Cotizacion::ESTADO_VENTA, $cotizacion->fresh()->estado);
        $this->assertContains(Cotizacion::ESTADO_VENTA, Cotizacion::estados());
        $this->assertDatabaseHas('ventas', ['cotizacion_id' => $cotizacion->id]);
    }

    private function datosBase(): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente estado venta',
            'rfc' => fake()->unique()->bothify('???######??#'),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);

        return [$usuario, $cliente];
    }
}
