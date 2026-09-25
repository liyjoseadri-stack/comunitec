<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaVenta;
use App\Models\PiezaInventario;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\ServicioConversionVenta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_esquema_de_ventas_conserva_origen_partidas_y_series(): void
    {
        $this->assertTrue(Schema::hasColumns('ventas', [
            'folio',
            'cotizacion_id',
            'cliente_id',
            'tipo_cliente',
            'nombre_cliente',
            'rfc_cliente',
            'correo_cliente',
            'telefono_cliente',
            'direccion_cliente',
            'codigo_postal_cliente',
            'usuario_id',
            'nombre_responsable',
            'correo_responsable',
            'vendida_en',
            'metodo_pago',
            'detalle_metodo_pago',
            'subtotal',
            'porcentaje_descuento',
            'total',
        ]));
        $this->assertTrue(Schema::hasColumns('partidas_venta', [
            'venta_id',
            'partida_cotizacion_id',
            'articulo_catalogo_id',
            'tipo',
            'descripcion',
            'cantidad',
            'precio_unitario',
            'subtotal',
        ]));
        $this->assertTrue(Schema::hasColumn('piezas_inventario', 'partida_venta_id'));
    }

    public function test_los_modelos_relacionan_la_venta_con_su_cotizacion_y_series(): void
    {
        $this->assertTrue(class_exists(Venta::class));
        $this->assertTrue(class_exists(PartidaVenta::class));

        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente empresarial',
            'rfc' => 'CEM010101AA1',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Equipo',
            'codigo' => 'EQU-VENTA',
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-VENTA-MODELO',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'porcentaje_descuento' => 0,
            'total' => 1000,
        ]);
        $partidaCotizada = $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Equipo cotizado',
            'cantidad' => 1,
            'precio_unitario' => 1000,
            'subtotal' => 1000,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-MODELO-1',
            'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cliente->id,
            'tipo_cliente' => $cliente->tipo,
            'nombre_cliente' => $cliente->nombre,
            'rfc_cliente' => $cliente->rfc,
            'correo_cliente' => $cliente->correo,
            'telefono_cliente' => $cliente->telefono,
            'direccion_cliente' => $cliente->direccion,
            'codigo_postal_cliente' => $cliente->codigo_postal,
            'usuario_id' => $usuario->id,
            'nombre_responsable' => $usuario->nombre,
            'correo_responsable' => $usuario->correo,
            'vendida_en' => now(),
            'metodo_pago' => 'transferencia',
            'subtotal' => 1000,
            'porcentaje_descuento' => 0,
            'total' => 1000,
        ]);
        $partidaVendida = $venta->partidas()->create([
            'partida_cotizacion_id' => $partidaCotizada->id,
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Equipo cotizado',
            'cantidad' => 1,
            'precio_unitario' => 1000,
            'subtotal' => 1000,
        ]);
        $pieza = PiezaInventario::create([
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'SERIE-VENTA-1',
            'estado' => 'entregada',
            'partida_venta_id' => $partidaVendida->id,
        ]);

        $this->assertTrue($cotizacion->fresh()->venta->is($venta));
        $this->assertTrue($venta->cotizacion->is($cotizacion));
        $this->assertTrue($venta->cliente->is($cliente));
        $this->assertTrue($venta->responsable->is($usuario));
        $this->assertTrue($partidaVendida->piezas->contains($pieza));
        $this->assertTrue($pieza->partidaVenta->is($partidaVendida));
    }

    public function test_el_comercial_convierte_una_aceptada_sin_descontar_inventario_dos_veces(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
            'piezaDisponible' => $piezaDisponible,
        ] = $this->prepararCotizacionAceptada();

        $respuesta = $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ]);

        $respuesta->assertRedirect();

        $venta = Venta::sole();
        $this->assertSame($cotizacion->id, $venta->cotizacion_id);
        $this->assertSame($cotizacion->cliente_id, $venta->cliente_id);
        $this->assertSame($usuario->id, $venta->usuario_id);
        $this->assertSame('2700.00', $venta->subtotal);
        $this->assertSame('10.00', $venta->porcentaje_descuento);
        $this->assertSame('2430.00', $venta->total);
        $this->assertCount(3, $venta->partidas);
        $this->assertDatabaseHas('partidas_venta', [
            'venta_id' => $venta->id,
            'descripcion' => 'Equipo físico',
            'precio_unitario' => 1000,
            'subtotal' => 2000,
        ]);
        foreach ($piezas as $pieza) {
            $this->assertSame('entregada', $pieza->fresh()->estado);
            $this->assertNull($pieza->fresh()->cotizacion_id);
            $this->assertNotNull($pieza->fresh()->partida_venta_id);
        }
        $this->assertSame('disponible', $piezaDisponible->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->vence_en);
    }

    public function test_la_venta_conserva_los_datos_historicos_del_cliente(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $nombreResponsable = $usuario->nombre;
        $correoResponsable = $usuario->correo;

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $venta = Venta::sole();
        $cotizacion->cliente->update([
            'nombre' => 'Nombre modificado después de la venta',
            'correo' => 'nuevo@example.test',
        ]);
        $usuario->update([
            'nombre' => 'Responsable modificado después de la venta',
            'correo' => 'responsable-nuevo@example.test',
        ]);

        $this->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertSee('Cliente de venta')
            ->assertSee('venta@example.test')
            ->assertSee($nombreResponsable)
            ->assertSee($correoResponsable)
            ->assertDontSee('Nombre modificado después de la venta')
            ->assertDontSee('nuevo@example.test')
            ->assertDontSee('Responsable modificado después de la venta')
            ->assertDontSee('responsable-nuevo@example.test');
    }

    public function test_una_cotizacion_convertida_no_puede_generar_otra_venta(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $datos = [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ];

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", $datos)
            ->assertRedirect();
        $this->post("/cotizaciones/{$cotizacion->id}/venta", $datos)
            ->assertRedirect()
            ->assertSessionHasErrors([
                'cotizacion' => 'Esta cotización ya fue convertida en venta.',
            ]);

        $this->assertSame(1, Venta::count());
    }

    public function test_el_metodo_otro_conserva_y_muestra_su_descripcion(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_OTRO,
            'detalle_metodo_pago' => 'Crédito autorizado por dirección',
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $venta = Venta::sole();
        $this->assertSame(Venta::METODO_OTRO, $venta->metodo_pago);
        $this->assertSame(
            'Crédito autorizado por dirección',
            $venta->detalle_metodo_pago
        );

        $this->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertSee('Otro')
            ->assertSee('Crédito autorizado por dirección');
    }

    public function test_una_falla_intermedia_revierte_la_venta_y_la_entrega_de_series(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $vencimientoOriginal = $cotizacion->vence_en;

        DB::listen(function (QueryExecuted $consulta): void {
            if (str_contains($consulta->sql, 'insert into "partidas_venta"')) {
                throw new \RuntimeException('Falla simulada después de crear la venta.');
            }
        });

        try {
            app(ServicioConversionVenta::class)->convertir(
                $cotizacion,
                $usuario,
                Venta::METODO_EFECTIVO,
                null,
                [
                    $partidaProducto->id => $piezas->pluck('id')->all(),
                ]
            );
            $this->fail('La conversión debía interrumpirse por la falla simulada.');
        } catch (\RuntimeException $excepcion) {
            $this->assertSame(
                'Falla simulada después de crear la venta.',
                $excepcion->getMessage()
            );
        }

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, DB::table('partidas_venta')->count());
        $this->assertTrue(
            $cotizacion->fresh()->vence_en->equalTo($vencimientoOriginal)
        );
        foreach ($piezas as $pieza) {
            $this->assertSame('reservada', $pieza->fresh()->estado);
            $this->assertSame($cotizacion->id, $pieza->fresh()->cotizacion_id);
            $this->assertNull($pieza->fresh()->partida_venta_id);
        }
    }

    public function test_series_incompletas_revierten_toda_la_conversion(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_TARJETA,
            'series' => [
                $partidaProducto->id => [
                    $piezas->first()->id,
                ],
            ],
        ])->assertRedirect()->assertSessionHasErrors("series.{$partidaProducto->id}");

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, DB::table('partidas_venta')->count());
        foreach ($piezas as $pieza) {
            $this->assertSame('reservada', $pieza->fresh()->estado);
            $this->assertSame($cotizacion->id, $pieza->fresh()->cotizacion_id);
            $this->assertNull($pieza->fresh()->partida_venta_id);
        }
    }

    public function test_no_se_puede_entregar_una_serie_que_no_esta_reservada(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
            'piezaDisponible' => $piezaDisponible,
        ] = $this->prepararCotizacionAceptada();

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaProducto->id => [
                    $piezas->first()->id,
                    $piezaDisponible->id,
                ],
            ],
        ])->assertRedirect()->assertSessionHasErrors("series.{$partidaProducto->id}");

        $this->assertSame(0, Venta::count());
        $this->assertSame('disponible', $piezaDisponible->fresh()->estado);
        $this->assertSame(2, PiezaInventario::where('cotizacion_id', $cotizacion->id)
            ->where('estado', 'reservada')
            ->count());
    }

    public function test_el_metodo_otro_requiere_una_descripcion(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
        ] = $this->prepararCotizacionAceptada();

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_OTRO,
            'detalle_metodo_pago' => '',
        ])->assertRedirect()->assertSessionHasErrors('detalle_metodo_pago');

        $this->assertSame(0, Venta::count());
    }

    public function test_una_cotizacion_no_aceptada_no_puede_convertirse(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
        ] = $this->prepararCotizacionAceptada();
        $cotizacion->update([
            'estado' => 'pendiente',
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
        ])->assertRedirect()->assertSessionHasErrors('cotizacion');

        $this->assertSame(0, Venta::count());
    }

    public function test_una_aceptada_muestra_metodo_de_pago_y_series_reservadas(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();

        $respuesta = $this->actingAs($usuario)->get("/cotizaciones/{$cotizacion->id}");

        $respuesta->assertOk()
            ->assertSee('Convertir en venta')
            ->assertSee('Método de pago')
            ->assertSee('Efectivo')
            ->assertSee('Transferencia')
            ->assertSee('Tarjeta')
            ->assertSee('Otro');
        foreach ($piezas as $pieza) {
            $respuesta->assertSee($pieza->numero_serie);
        }
        $respuesta->assertSee(
            'name="series['.$partidaProducto->id.'][]"',
            false
        );
    }

    public function test_consulta_puede_ver_ventas_pero_no_convertir_cotizaciones(): void
    {
        [
            'usuario' => $usuarioComercial,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $this->actingAs($usuarioComercial)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();
        $venta = Venta::sole();
        $consulta = Usuario::factory()->create([
            'rol' => Usuario::ROL_CONSULTA,
        ]);

        $this->actingAs($consulta)->get('/ventas')
            ->assertOk()
            ->assertSee($venta->folio)
            ->assertDontSee('<form', false);
        $this->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertSee($venta->folio)
            ->assertSee('SERIE-CONVERSION-1')
            ->assertSee('SERIE-CONVERSION-2')
            ->assertDontSee('<form', false);
        $this->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
        ])->assertForbidden();
    }

    public function test_una_cotizacion_convertida_ya_no_admite_cambios_ni_cancelacion(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $this->post("/cotizaciones/{$cotizacion->id}/cancelar")
            ->assertStatus(422);
        $this->put("/cotizaciones/{$cotizacion->id}", [
            'cliente_id' => $cotizacion->cliente_id,
            'area_solicitante' => 'Área modificada',
            'porcentaje_descuento' => 10,
        ])->assertStatus(422);
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partidaProducto->id}", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $partidaProducto->articulo_catalogo_id,
            'descripcion' => 'Intento de cambio',
            'cantidad' => 2,
        ])->assertStatus(422);

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('Equipo físico', $partidaProducto->fresh()->descripcion);
        foreach ($piezas as $pieza) {
            $this->assertSame('entregada', $pieza->fresh()->estado);
        }
    }

    public function test_inventario_rastrea_una_serie_entregada_hasta_la_venta_y_el_cliente(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();
        $venta = Venta::sole();

        $this->get('/inventario')
            ->assertOk()
            ->assertSee('SERIE-CONVERSION-1')
            ->assertSee($venta->folio)
            ->assertSee('Cliente de venta')
            ->assertSee('href="'.route('ventas.detalle', $venta).'"', false);
    }

    private function prepararCotizacionAceptada(): array
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente de venta',
            'rfc' => 'CVE010101AA1',
            'correo' => 'venta@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Equipo físico',
            'codigo' => 'EQU-CONVERSION',
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 3,
        ]);
        $servicio = ArticuloCatalogo::create([
            'tipo' => 'servicio',
            'nombre' => 'Instalación',
            'codigo' => 'SER-CONVERSION',
            'unidad' => 'servicio',
            'precio' => 500,
            'existencias' => 0,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-CONVERSION-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'vence_en' => now()->addDays(5),
            'porcentaje_descuento' => 10,
            'total' => 2430,
        ]);
        $partidaProducto = $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $producto->id,
            'tipo' => 'producto',
            'descripcion' => 'Equipo físico',
            'cantidad' => 2,
            'precio_unitario' => 1000,
            'subtotal' => 2000,
        ]);
        $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $servicio->id,
            'tipo' => 'servicio',
            'descripcion' => 'Instalación',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);
        $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Material adicional',
            'cantidad' => 2,
            'precio_unitario' => 100,
            'subtotal' => 200,
        ]);
        $piezas = new Collection;
        foreach (['SERIE-CONVERSION-1', 'SERIE-CONVERSION-2'] as $serie) {
            $piezas->push(PiezaInventario::create([
                'articulo_catalogo_id' => $producto->id,
                'numero_serie' => $serie,
                'estado' => 'reservada',
                'cotizacion_id' => $cotizacion->id,
            ]));
        }
        $piezaDisponible = PiezaInventario::create([
            'articulo_catalogo_id' => $producto->id,
            'numero_serie' => 'SERIE-DISPONIBLE',
            'estado' => 'disponible',
        ]);

        return compact(
            'usuario',
            'cotizacion',
            'partidaProducto',
            'piezas',
            'piezaDisponible'
        );
    }
}
