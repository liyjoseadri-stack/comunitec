<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreacionCotizacionCompletaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_reune_encabezado_conceptos_y_totales_en_un_solo_formulario(): void
    {
        $datos = $this->catalogo();

        $this->actingAs($datos['usuario'])
            ->get(route('cotizaciones.listado'))
            ->assertOk()
            ->assertSee('Crear cotización')
            ->assertSee('Buscar cliente por nombre o RFC')
            ->assertSee('Conceptos de la cotización')
            ->assertSee('Subtotal sin IVA')
            ->assertSee('Guardar cotización')
            ->assertSee('data-creador-cotizacion', false);
    }

    public function test_guarda_la_cotizacion_y_todos_sus_conceptos_en_una_transaccion(): void
    {
        $datos = $this->catalogo();

        $respuesta = $this->actingAs($datos['usuario'])
            ->postJson('/api/cotizaciones', [
                'cliente_id' => $datos['cliente']->id,
                'area_solicitante' => 'Infraestructura',
                'notas' => 'Entrega coordinada con sistemas.',
                'items' => [
                    [
                        'tipo' => 'producto',
                        'producto_id' => $datos['producto']->id,
                        'descripcion' => 'Memoria para estaciones administrativas',
                        'cantidad' => 2,
                        'precio_unitario' => 1,
                        'porcentaje_descuento' => 10,
                    ],
                    [
                        'tipo' => 'servicio',
                        'servicio_id' => $datos['servicio']->id,
                        'descripcion' => 'Configuración y pruebas',
                        'cantidad' => 1,
                        'precio_unitario' => 1,
                        'porcentaje_descuento' => 0,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('mensaje', 'La cotización se guardó correctamente.');

        $cotizacion = Cotizacion::with('partidas')->firstOrFail();

        $this->assertSame('borrador', $cotizacion->estado);
        $this->assertSame('Entrega coordinada con sistemas.', $cotizacion->notas);
        $this->assertCount(2, $cotizacion->partidas);
        $this->assertSame('900.00', $cotizacion->partidas[0]->precio_unitario);
        $this->assertSame('10.00', $cotizacion->partidas[0]->porcentaje_descuento);
        $this->assertSame('1620.00', $cotizacion->partidas[0]->subtotal);
        $this->assertSame('2120.00', $cotizacion->total);
        $respuesta->assertJsonPath('redireccion', route('cotizaciones.detalle', $cotizacion));
    }

    public function test_un_concepto_invalido_impide_crear_toda_la_cotizacion(): void
    {
        $datos = $this->catalogo();

        $this->actingAs($datos['usuario'])
            ->postJson('/api/cotizaciones', [
                'cliente_id' => $datos['cliente']->id,
                'items' => [[
                    'tipo' => 'producto',
                    'producto_id' => $datos['producto']->id,
                    'descripcion' => 'Cantidad inválida',
                    'cantidad' => 0,
                    'precio_unitario' => 900,
                    'porcentaje_descuento' => 0,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.cantidad');

        $this->assertDatabaseCount('cotizaciones', 0);
        $this->assertDatabaseCount('partidas_cotizacion', 0);
    }

    private function catalogo(): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Tecnología del Sureste, S.A. de C.V.',
            'rfc' => 'TSU260101AA1',
            'correo' => 'compras@tecnologiasureste.example',
            'telefono' => '9611002000',
            'direccion' => 'Tuxtla Gutiérrez, Chiapas',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Memoria RAM 16 GB DDR4',
            'codigo' => 'RAM-16-DDR4',
            'unidad' => 'pieza',
            'precio' => 900,
            'existencias' => 5,
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Instalación de memoria',
            'codigo' => 'SER-INST-RAM',
            'descripcion' => 'Instalación y pruebas',
            'unidad' => 'servicio',
            'precio' => 500,
            'activo' => true,
        ]);

        return compact('usuario', 'cliente', 'producto', 'servicio');
    }
}
