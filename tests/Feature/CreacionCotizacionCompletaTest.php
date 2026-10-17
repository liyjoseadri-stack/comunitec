<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreacionCotizacionCompletaTest extends TestCase
{
    use RefreshDatabase;

    public static function entradasConPrecisionExcesiva(): iterable
    {
        yield 'cantidad de servicio' => ['cantidad', 1.333, 'items.0.cantidad'];
        yield 'descuento de concepto' => ['porcentaje_descuento', 7.255, 'items.0.porcentaje_descuento'];
        yield 'descuento general' => ['descuento_general', 7.255, 'porcentaje_descuento'];
        yield 'precio unitario' => ['precio_unitario', 12.345, 'items.0.precio_unitario'];
        yield 'cantidad de producto fraccionaria' => ['cantidad', 1.33, 'items.0.cantidad', 'producto'];
    }

    #[DataProvider('entradasConPrecisionExcesiva')]
    public function test_la_api_rechaza_precision_que_no_puede_conservar_sin_crear_datos(
        string $campo,
        float $valor,
        string $claveError,
        string $tipo = 'servicio'
    ): void {
        $datos = $this->catalogo();
        $item = [
            'tipo' => $tipo,
            'servicio_id' => $tipo === 'servicio' ? $datos['servicio']->id : null,
            'producto_id' => $tipo === 'producto' ? $datos['producto']->id : null,
            'descripcion' => 'Servicio de prueba',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'porcentaje_descuento' => 0,
        ];
        $entrada = ['cliente_id' => $datos['cliente']->id, 'items' => [$item]];
        if ($campo === 'descuento_general') {
            $entrada['porcentaje_descuento'] = $valor;
        } else {
            $entrada['items'][0][$campo] = $valor;
        }

        $this->actingAs($datos['usuario'])->postJson('/api/cotizaciones', $entrada)
            ->assertUnprocessable()->assertJsonValidationErrors($claveError);

        $this->assertDatabaseCount('cotizaciones', 0);
        $this->assertDatabaseCount('partidas_cotizacion', 0);
        $this->assertSame(5, $datos['producto']->fresh()->existencias);
    }

    public function test_la_ruta_heredada_no_puede_crear_encabezados_vacios(): void
    {
        $datos = $this->catalogo();

        $this->actingAs($datos['usuario'])->post('/cotizaciones', [
            'cliente_id' => $datos['cliente']->id,
        ])->assertStatus(405);

        $this->assertDatabaseCount('cotizaciones', 0);
        $this->assertDatabaseCount('partidas_cotizacion', 0);
    }

    public function test_la_pantalla_reune_encabezado_conceptos_y_totales_en_un_solo_formulario(): void
    {
        $datos = $this->catalogo();

        $this->actingAs($datos['usuario'])
            ->get(route('cotizaciones.crear'))
            ->assertOk()
            ->assertSee('Crear cotización')
            ->assertSee('Buscar cliente por nombre o RFC')
            ->assertSee('Conceptos de la cotización')
            ->assertSee('Subtotal sin IVA')
            ->assertSee('Guardar cotización')
            ->assertSee('data-creador-cotizacion', false);

        $this->actingAs($datos['usuario'])
            ->get(route('cotizaciones.listado'))
            ->assertOk()
            ->assertSee('Cotizaciones registradas')
            ->assertDontSee('data-creador-cotizacion', false);
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

    public function test_el_flujo_completo_admite_servicios_fraccionarios_y_descuentos_con_redondeo(): void
    {
        Mail::fake();
        $datos = $this->catalogo();
        $datos['producto']->update(['precio' => 19.99, 'requiere_numero_serie' => false]);
        $datos['servicio']->update(['precio' => 13.37]);

        $this->actingAs($datos['usuario'])->postJson('/api/cotizaciones', [
            'cliente_id' => $datos['cliente']->id,
            'porcentaje_descuento' => 7.5,
            'items' => [
                [
                    'tipo' => 'producto',
                    'producto_id' => $datos['producto']->id,
                    'descripcion' => 'Primer lote',
                    'cantidad' => 2,
                    'precio_unitario' => 1,
                    'porcentaje_descuento' => 7.25,
                ],
                [
                    'tipo' => 'producto',
                    'producto_id' => $datos['producto']->id,
                    'descripcion' => 'Segundo lote',
                    'cantidad' => 1,
                    'precio_unitario' => 1,
                    'porcentaje_descuento' => 100,
                ],
                [
                    'tipo' => 'servicio',
                    'servicio_id' => $datos['servicio']->id,
                    'descripcion' => 'Trabajo parcial',
                    'cantidad' => 1.33,
                    'precio_unitario' => 1,
                    'porcentaje_descuento' => 3.5,
                ],
            ],
        ])->assertCreated();
        $cotizacion = Cotizacion::sole();
        $this->assertSame('50.17', $cotizacion->total);
        $partidasOriginales = $cotizacion->partidas()->orderBy('id')->get()->map(fn ($partida): array => [
            'cantidad' => $partida->cantidad,
            'precio_unitario' => $partida->precio_unitario,
            'porcentaje_descuento' => $partida->porcentaje_descuento,
            'subtotal' => $partida->subtotal,
        ])->all();
        $datos['producto']->update(['precio' => 500]);
        $datos['servicio']->update(['precio' => 1000]);

        $this->post("/cotizaciones/{$cotizacion->id}/enviar", [
            'token_envio' => '33333333-3333-4333-8333-333333333333',
        ])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/cotizaciones/{$cotizacion->id}/aceptar")
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('venta', $cotizacion->fresh()->estado);
        $this->assertSame(2, $datos['producto']->fresh()->existencias);
        $this->assertSame('50.17', Venta::sole()->total);
        $this->assertDatabaseCount('partidas_venta', 3);
        $this->assertSame($partidasOriginales, Venta::sole()->partidas()->orderBy('id')->get()
            ->map(fn ($partida): array => [
                'cantidad' => $partida->cantidad,
                'precio_unitario' => $partida->precio_unitario,
                'porcentaje_descuento' => $partida->porcentaje_descuento,
                'subtotal' => $partida->subtotal,
            ])->all());
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
