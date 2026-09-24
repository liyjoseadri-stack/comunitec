<?php

namespace Tests\Feature;

use App\Http\Controllers\ControladorCotizaciones;
use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Models\Usuario;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EdicionCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_editar_una_aceptada_libera_reservas_y_exige_nueva_aceptacion(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('accepted');
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [

            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,

            'description' => 'Monitor actualizado',
            'quantity' => 2,
            'unit_price' => 500,

        ])->assertRedirect();
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertNull($cotizacion->fresh()->accepted_at);
        $this->assertSame('950.00', $cotizacion->fresh()->total);
        $this->assertDatabaseHas('inventory_units', [
            'id' => $pieza->id,
            'status' => 'available',
            'quote_id' => null,
        ]);
        $this->assertDatabaseHas('quote_lines', [
            'id' => $partida->id,
            'description' => 'Monitor actualizado',
            'quantity' => 2,
        ]);
    }

    public function test_datos_invalidos_conservan_la_aceptacion_y_sus_reservas(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('accepted');
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [

            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,

            'description' => 'Monitor',
            'quantity' => 1.5,
            'unit_price' => 500,

        ])->assertSessionHasErrors('quantity');
        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_no_se_pueden_eliminar_partidas_de_cotizaciones_canceladas(): void
    {
        [$cotizacion, $partida] = $this->preparar('cancelled');
        $this->delete("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertStatus(422);
        $this->assertDatabaseHas('quote_lines', [
            'id' => $partida->id,
        ]);
    }

    public function test_editar_encabezado_recalcula_y_libera_la_aceptacion(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('accepted');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'customer_id' => $cotizacion->customer_id,
            'area_requesting' => 'Compras',

            'discount_percent' => 10,
            'total' => 1,
            'status' => 'accepted',

        ])->assertRedirect();
        $this->assertSame('450.00', $cotizacion->fresh()->total);
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertSame('Compras', $cotizacion->fresh()->area_requesting);
        $this->assertNull($cotizacion->fresh()->accepted_at);
        $this->assertSame('available', $pieza->fresh()->status);
    }

    public function test_descuento_fuera_del_rango_conserva_las_reservas(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('accepted');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'customer_id' => $cotizacion->customer_id,
            'discount_percent' => 3,

        ])->assertSessionHasErrors('discount_percent');
        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_guardar_encabezado_sin_cambios_conserva_la_aceptacion(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('accepted');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'customer_id' => $cotizacion->customer_id,
            'discount_percent' => 5,

        ])->assertRedirect();
        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_no_se_puede_editar_encabezado_de_una_cancelada(): void
    {
        [$cotizacion] = $this->preparar('cancelled');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'customer_id' => $cotizacion->customer_id,
            'discount_percent' => 10,

        ])->assertStatus(422);
        $this->assertSame('475.00', $cotizacion->fresh()->total);
    }

    public function test_crear_rechaza_descuentos_menores_al_cinco_por_ciento(): void
    {
        [$cotizacion] = $this->preparar('draft');
        $this->post('/cotizaciones', [
            'customer_id' => $cotizacion->customer_id,
            'discount_percent' => 3,
        ])
            ->assertSessionHasErrors('discount_percent');
        $this->assertDatabaseCount('quotes', 1);
    }

    public function test_dos_cotizaciones_en_el_mismo_instante_tienen_folios_distintos(): void
    {
        $this->freezeTime();
        [$cotizacion] = $this->preparar('draft');
        foreach ([
            1,
            2,
        ] as $intento) {
            $this->post('/cotizaciones', [
                'customer_id' => $cotizacion->customer_id,
            ])
                ->assertRedirect('/cotizaciones');
        }
        $this->assertSame(3, Cotizacion::distinct()->count('folio'));
        $this->assertSame('0.00', Cotizacion::latest('id')->first()->discount_percent);
    }

    public function test_la_advertencia_suma_partidas_del_mismo_producto(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('draft');
        $pieza->update([
            'status' => 'available',
            'quote_id' => null,
        ]);
        $cotizacion->lines()->create([
            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,
            'description' => 'Monitor adicional',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);
        $this->get("/cotizaciones/{$cotizacion->id}")->assertOk()
            ->assertSee('Monitor: se cotizaron 2 piezas y hay 1 disponibles.');
    }

    public function test_faltantes_en_partidas_repetidas_no_dejan_reservas_parciales(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('pending');
        $pieza->update([
            'status' => 'available',
            'quote_id' => null,
        ]);
        $cotizacion->lines()->create([
            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,
            'description' => 'Monitor adicional',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);
        $this->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertSessionHas('error');
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertSame('available', $pieza->fresh()->status);
        $this->assertNull($pieza->fresh()->quote_id);
    }

    public function test_no_se_acepta_una_cotizacion_fuera_de_vigencia(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pending');
        $cotizacion->update([
            'expires_at' => now()->subSecond(),
        ]);
        $pieza->update([
            'status' => 'available',
            'quote_id' => null,
        ]);
        $this->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertSessionHasErrors('cotizacion');
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertSame('available', $pieza->fresh()->status);
    }

    public function test_el_servicio_no_puede_reservar_dos_veces_una_cotizacion(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('accepted');
        $adicional = PiezaInventario::create([
            'catalog_item_id' => $pieza->catalog_item_id,
            'serial_number' => 'OTRA-SERIE',
            'status' => 'available',
        ]);
        try {
            app(ServicioInventarioCotizacion::class)->reservar($cotizacion);
            $this->fail('La segunda reserva debió bloquearse.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('cotizacion', $excepcion->errors());
        }
        $this->assertSame('available', $adicional->fresh()->status);
    }

    public function test_el_vencimiento_respeta_una_vigencia_actualizada(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('accepted');
        $cotizacion->update(['expires_at' => now()->subMinute()]);
        Cotizacion::whereKey($cotizacion->id)->update(['expires_at' => now()->addDays(5)]);

        $liberada = app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true);

        $this->assertFalse($liberada);
        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_una_copia_antigua_no_cancela_una_cotizacion_que_volvio_a_pendiente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('accepted');
        Cotizacion::whereKey($cotizacion->id)->update(['status' => 'pending']);

        $liberada = app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true);

        $this->assertFalse($liberada);
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_el_formulario_muestra_el_error_y_conserva_los_datos(): void
    {
        [$cotizacion] = $this->preparar('draft');

        $this->from('/cotizaciones')->post('/cotizaciones', [
            'customer_id' => $cotizacion->customer_id,
            'area_requesting' => 'Administración',
            'discount_percent' => 3,
        ])->assertRedirect('/cotizaciones')->assertSessionHasErrors('discount_percent');

        $this->get('/cotizaciones')->assertOk()
            ->assertSee('El descuento debe ser 0 (sin descuento) o estar entre 5% y 10%.')
            ->assertSee('value="Administración"', false)
            ->assertSee('value="3"', false);
    }

    public function test_cancelar_relee_el_estado_y_libera_una_aceptacion_reciente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pending');
        Cotizacion::whereKey($cotizacion->id)->update(['status' => 'accepted']);

        app(ControladorCotizaciones::class)->cancelar($cotizacion);

        $this->assertSame('cancelled', $cotizacion->fresh()->status);
        $this->assertSame('available', $pieza->fresh()->status);
        $this->assertNull($pieza->fresh()->quote_id);
    }

    public function test_rechazar_no_sobrescribe_una_aceptacion_reciente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pending');
        Cotizacion::whereKey($cotizacion->id)->update(['status' => 'accepted']);

        try {
            app(ControladorCotizaciones::class)->rechazar($cotizacion);
            $this->fail('Debe impedirse el rechazo de una cotización que ya fue aceptada.');
        } catch (HttpException $error) {
            $this->assertSame(422, $error->getStatusCode());
        }

        $this->assertSame('accepted', $cotizacion->fresh()->status);
        $this->assertSame('reserved', $pieza->fresh()->status);
    }

    public function test_no_se_puede_agregar_un_articulo_desactivado_por_solicitud_directa(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('draft');
        ArticuloCatalogo::whereKey($pieza->catalog_item_id)->update(['active' => false]);

        $this->post("/cotizaciones/{$cotizacion->id}/partidas", [
            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,
            'quantity' => 1,
        ])->assertSessionHasErrors('catalog_item_id');

        $this->assertDatabaseCount('quote_lines', 1);
        $this->assertSame('475.00', $cotizacion->fresh()->total);
    }

    public function test_editar_una_partida_conserva_el_precio_cotizado_aunque_cambie_el_catalogo(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('draft');
        ArticuloCatalogo::whereKey($pieza->catalog_item_id)->update(['price' => 900]);

        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [
            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,
            'description' => 'Monitor para recepción',
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('quote_lines', [
            'id' => $partida->id,
            'description' => 'Monitor para recepción',
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
        ]);
        $this->assertSame('950.00', $cotizacion->fresh()->total);
    }

    public function test_desactivar_el_articulo_no_bloquea_la_edicion_de_su_partida_historica(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('draft');
        ArticuloCatalogo::whereKey($pieza->catalog_item_id)->update(['active' => false]);

        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [
            'type' => 'product',
            'catalog_item_id' => $pieza->catalog_item_id,
            'description' => 'Monitor conservado en el historial',
            'quantity' => 2,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('quote_lines', [
            'id' => $partida->id,
            'description' => 'Monitor conservado en el historial',
            'unit_price' => 500,
            'subtotal' => 1000,
        ]);
    }

    private function preparar(string $estado): array
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($usuario);
        $cliente = Cliente::create([
            'type' => 'fisica',
            'name' => 'Ana',
            'rfc' => 'ANA010101AA1',
            'email' => 'ana@example.test',
            'phone' => '9610000000',
            'address' => 'Domicilio de prueba',
            'postal_code' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Monitor',
            'code' => 'MON-EDITAR',
            'unit' => 'pieza',
            'price' => 500,
            'stock' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-EDITAR',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => $estado,
            'accepted_at' => now(),
            'discount_percent' => 5,
            'total' => 475,
        ]);
        $partida = $cotizacion->lines()->create([
            'type' => 'product',
            'catalog_item_id' => $articulo->id,
            'description' => 'Monitor',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);
        $pieza = PiezaInventario::create([
            'catalog_item_id' => $articulo->id,
            'serial_number' => 'SERIE-EDITAR',
            'status' => 'reserved',
            'quote_id' => $cotizacion->id,
        ]);

        return [
            $cotizacion,
            $partida,
            $pieza,
        ];
    }
}
