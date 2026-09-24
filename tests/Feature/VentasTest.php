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
        $this->assertTrue(Schema::hasColumns('sales', [
            'folio',
            'quote_id',
            'customer_id',
            'customer_type',
            'customer_name',
            'customer_rfc',
            'customer_email',
            'customer_phone',
            'customer_address',
            'customer_postal_code',
            'user_id',
            'responsible_name',
            'responsible_email',
            'sold_at',
            'payment_method',
            'payment_method_detail',
            'subtotal',
            'discount_percent',
            'total',
        ]));
        $this->assertTrue(Schema::hasColumns('sale_lines', [
            'sale_id',
            'quote_line_id',
            'catalog_item_id',
            'type',
            'description',
            'quantity',
            'unit_price',
            'subtotal',
        ]));
        $this->assertTrue(Schema::hasColumn('inventory_units', 'sale_line_id'));
    }

    public function test_los_modelos_relacionan_la_venta_con_su_cotizacion_y_series(): void
    {
        $this->assertTrue(class_exists(Venta::class));
        $this->assertTrue(class_exists(PartidaVenta::class));

        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'type' => 'moral',
            'name' => 'Cliente empresarial',
            'rfc' => 'CEM010101AA1',
            'email' => 'cliente@example.test',
            'phone' => '9610000000',
            'address' => 'Domicilio de prueba',
            'postal_code' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Equipo',
            'code' => 'EQU-VENTA',
            'unit' => 'pieza',
            'price' => 1000,
            'stock' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-VENTA-MODELO',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'accepted',
            'discount_percent' => 0,
            'total' => 1000,
        ]);
        $partidaCotizada = $cotizacion->lines()->create([
            'catalog_item_id' => $articulo->id,
            'type' => 'product',
            'description' => 'Equipo cotizado',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-MODELO-1',
            'quote_id' => $cotizacion->id,
            'customer_id' => $cliente->id,
            'customer_type' => $cliente->type,
            'customer_name' => $cliente->name,
            'customer_rfc' => $cliente->rfc,
            'customer_email' => $cliente->email,
            'customer_phone' => $cliente->phone,
            'customer_address' => $cliente->address,
            'customer_postal_code' => $cliente->postal_code,
            'user_id' => $usuario->id,
            'responsible_name' => $usuario->name,
            'responsible_email' => $usuario->email,
            'sold_at' => now(),
            'payment_method' => 'transfer',
            'subtotal' => 1000,
            'discount_percent' => 0,
            'total' => 1000,
        ]);
        $partidaVendida = $venta->partidas()->create([
            'quote_line_id' => $partidaCotizada->id,
            'catalog_item_id' => $articulo->id,
            'type' => 'product',
            'description' => 'Equipo cotizado',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);
        $pieza = PiezaInventario::create([
            'catalog_item_id' => $articulo->id,
            'serial_number' => 'SERIE-VENTA-1',
            'status' => 'delivered',
            'sale_line_id' => $partidaVendida->id,
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
            'payment_method' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ]);

        $respuesta->assertRedirect();

        $venta = Venta::sole();
        $this->assertSame($cotizacion->id, $venta->quote_id);
        $this->assertSame($cotizacion->customer_id, $venta->customer_id);
        $this->assertSame($usuario->id, $venta->user_id);
        $this->assertSame('2700.00', $venta->subtotal);
        $this->assertSame('10.00', $venta->discount_percent);
        $this->assertSame('2430.00', $venta->total);
        $this->assertCount(3, $venta->partidas);
        $this->assertDatabaseHas('sale_lines', [
            'sale_id' => $venta->id,
            'description' => 'Equipo físico',
            'unit_price' => 1000,
            'subtotal' => 2000,
        ]);
        foreach ($piezas as $pieza) {
            $this->assertSame('delivered', $pieza->fresh()->status);
            $this->assertNull($pieza->fresh()->quote_id);
            $this->assertNotNull($pieza->fresh()->sale_line_id);
        }
        $this->assertSame('available', $piezaDisponible->fresh()->status);
        $this->assertNull($cotizacion->fresh()->expires_at);
    }

    public function test_la_venta_conserva_los_datos_historicos_del_cliente(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
            'partidaProducto' => $partidaProducto,
            'piezas' => $piezas,
        ] = $this->prepararCotizacionAceptada();
        $nombreResponsable = $usuario->name;
        $correoResponsable = $usuario->email;

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'payment_method' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $venta = Venta::sole();
        $cotizacion->customer->update([
            'name' => 'Nombre modificado después de la venta',
            'email' => 'nuevo@example.test',
        ]);
        $usuario->update([
            'name' => 'Responsable modificado después de la venta',
            'email' => 'responsable-nuevo@example.test',
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
            'payment_method' => Venta::METODO_EFECTIVO,
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
            'payment_method' => Venta::METODO_OTRO,
            'payment_method_detail' => 'Crédito autorizado por dirección',
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $venta = Venta::sole();
        $this->assertSame(Venta::METODO_OTRO, $venta->payment_method);
        $this->assertSame(
            'Crédito autorizado por dirección',
            $venta->payment_method_detail
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
        $vencimientoOriginal = $cotizacion->expires_at;

        DB::listen(function (QueryExecuted $consulta): void {
            if (str_contains($consulta->sql, 'insert into "sale_lines"')) {
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
        $this->assertSame(0, DB::table('sale_lines')->count());
        $this->assertTrue(
            $cotizacion->fresh()->expires_at->equalTo($vencimientoOriginal)
        );
        foreach ($piezas as $pieza) {
            $this->assertSame('reserved', $pieza->fresh()->status);
            $this->assertSame($cotizacion->id, $pieza->fresh()->quote_id);
            $this->assertNull($pieza->fresh()->sale_line_id);
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
            'payment_method' => Venta::METODO_TARJETA,
            'series' => [
                $partidaProducto->id => [
                    $piezas->first()->id,
                ],
            ],
        ])->assertRedirect()->assertSessionHasErrors("series.{$partidaProducto->id}");

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, DB::table('sale_lines')->count());
        foreach ($piezas as $pieza) {
            $this->assertSame('reserved', $pieza->fresh()->status);
            $this->assertSame($cotizacion->id, $pieza->fresh()->quote_id);
            $this->assertNull($pieza->fresh()->sale_line_id);
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
            'payment_method' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaProducto->id => [
                    $piezas->first()->id,
                    $piezaDisponible->id,
                ],
            ],
        ])->assertRedirect()->assertSessionHasErrors("series.{$partidaProducto->id}");

        $this->assertSame(0, Venta::count());
        $this->assertSame('available', $piezaDisponible->fresh()->status);
        $this->assertSame(2, PiezaInventario::where('quote_id', $cotizacion->id)
            ->where('status', 'reserved')
            ->count());
    }

    public function test_el_metodo_otro_requiere_una_descripcion(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
        ] = $this->prepararCotizacionAceptada();

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'payment_method' => Venta::METODO_OTRO,
            'payment_method_detail' => '',
        ])->assertRedirect()->assertSessionHasErrors('payment_method_detail');

        $this->assertSame(0, Venta::count());
    }

    public function test_una_cotizacion_no_aceptada_no_puede_convertirse(): void
    {
        [
            'usuario' => $usuario,
            'cotizacion' => $cotizacion,
        ] = $this->prepararCotizacionAceptada();
        $cotizacion->update([
            'status' => 'pending',
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/venta", [
            'payment_method' => Venta::METODO_EFECTIVO,
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
            $respuesta->assertSee($pieza->serial_number);
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
            'payment_method' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();
        $venta = Venta::sole();
        $consulta = Usuario::factory()->create([
            'role' => Usuario::ROL_CONSULTA,
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
            'payment_method' => Venta::METODO_EFECTIVO,
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
            'payment_method' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaProducto->id => $piezas->pluck('id')->all(),
            ],
        ])->assertRedirect();

        $this->post("/cotizaciones/{$cotizacion->id}/cancelar")
            ->assertStatus(422);
        $this->put("/cotizaciones/{$cotizacion->id}", [
            'customer_id' => $cotizacion->customer_id,
            'area_requesting' => 'Área modificada',
            'discount_percent' => 10,
        ])->assertStatus(422);
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partidaProducto->id}", [
            'type' => 'product',
            'catalog_item_id' => $partidaProducto->catalog_item_id,
            'description' => 'Intento de cambio',
            'quantity' => 2,
        ])->assertStatus(422);

        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('Equipo físico', $partidaProducto->fresh()->description);
        foreach ($piezas as $pieza) {
            $this->assertSame('delivered', $pieza->fresh()->status);
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
            'payment_method' => Venta::METODO_TRANSFERENCIA,
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
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'type' => 'moral',
            'name' => 'Cliente de venta',
            'rfc' => 'CVE010101AA1',
            'email' => 'venta@example.test',
            'phone' => '9610000000',
            'address' => 'Domicilio de prueba',
            'postal_code' => '29000',
        ]);
        $producto = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Equipo físico',
            'code' => 'EQU-CONVERSION',
            'unit' => 'pieza',
            'price' => 1000,
            'stock' => 3,
        ]);
        $servicio = ArticuloCatalogo::create([
            'type' => 'service',
            'name' => 'Instalación',
            'code' => 'SER-CONVERSION',
            'unit' => 'servicio',
            'price' => 500,
            'stock' => 0,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-CONVERSION-1',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'expires_at' => now()->addDays(5),
            'discount_percent' => 10,
            'total' => 2430,
        ]);
        $partidaProducto = $cotizacion->lines()->create([
            'catalog_item_id' => $producto->id,
            'type' => 'product',
            'description' => 'Equipo físico',
            'quantity' => 2,
            'unit_price' => 1000,
            'subtotal' => 2000,
        ]);
        $cotizacion->lines()->create([
            'catalog_item_id' => $servicio->id,
            'type' => 'service',
            'description' => 'Instalación',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);
        $cotizacion->lines()->create([
            'type' => 'other',
            'description' => 'Material adicional',
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
        $piezas = new Collection;
        foreach (['SERIE-CONVERSION-1', 'SERIE-CONVERSION-2'] as $serie) {
            $piezas->push(PiezaInventario::create([
                'catalog_item_id' => $producto->id,
                'serial_number' => $serie,
                'status' => 'reserved',
                'quote_id' => $cotizacion->id,
            ]));
        }
        $piezaDisponible = PiezaInventario::create([
            'catalog_item_id' => $producto->id,
            'serial_number' => 'SERIE-DISPONIBLE',
            'status' => 'available',
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
