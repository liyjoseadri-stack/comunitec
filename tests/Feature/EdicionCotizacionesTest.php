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
        [$cotizacion, $partida, $pieza] = $this->preparar('aceptada');
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [

            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,

            'descripcion' => 'Monitor actualizado',
            'cantidad' => 2,
            'precio_unitario' => 500,

        ])->assertRedirect();
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->aceptada_en);
        $this->assertSame('950.00', $cotizacion->fresh()->total);
        $this->assertDatabaseHas('piezas_inventario', [
            'id' => $pieza->id,
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
        $this->assertDatabaseHas('partidas_cotizacion', [
            'id' => $partida->id,
            'descripcion' => 'Monitor actualizado',
            'cantidad' => 2,
        ]);
    }

    public function test_datos_invalidos_conservan_la_aceptacion_y_sus_reservas(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('aceptada');
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [

            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,

            'descripcion' => 'Monitor',
            'cantidad' => 1.5,
            'precio_unitario' => 500,

        ])->assertSessionHasErrors('cantidad');
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_no_se_pueden_eliminar_partidas_de_cotizaciones_canceladas(): void
    {
        [$cotizacion, $partida] = $this->preparar('cancelada');
        $this->delete("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertStatus(422);
        $this->assertDatabaseHas('partidas_cotizacion', [
            'id' => $partida->id,
        ]);
    }

    public function test_editar_encabezado_recalcula_y_libera_la_aceptacion(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('aceptada');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'cliente_id' => $cotizacion->cliente_id,
            'area_solicitante' => 'Compras',

            'porcentaje_descuento' => 10,
            'total' => 1,
            'estado' => 'aceptada',

        ])->assertRedirect();
        $this->assertSame('450.00', $cotizacion->fresh()->total);
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('Compras', $cotizacion->fresh()->area_solicitante);
        $this->assertNull($cotizacion->fresh()->aceptada_en);
        $this->assertSame('disponible', $pieza->fresh()->estado);
    }

    public function test_descuento_fuera_del_rango_conserva_las_reservas(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('aceptada');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'cliente_id' => $cotizacion->cliente_id,
            'porcentaje_descuento' => 3,

        ])->assertSessionHasErrors('porcentaje_descuento');
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_guardar_encabezado_sin_cambios_conserva_la_aceptacion(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('aceptada');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'cliente_id' => $cotizacion->cliente_id,
            'porcentaje_descuento' => 5,

        ])->assertRedirect();
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_no_se_puede_editar_encabezado_de_una_cancelada(): void
    {
        [$cotizacion] = $this->preparar('cancelada');
        $this->put("/cotizaciones/{$cotizacion->id}", [

            'cliente_id' => $cotizacion->cliente_id,
            'porcentaje_descuento' => 10,

        ])->assertStatus(422);
        $this->assertSame('475.00', $cotizacion->fresh()->total);
    }

    public function test_crear_rechaza_descuentos_menores_al_cinco_por_ciento(): void
    {
        [$cotizacion] = $this->preparar('borrador');
        $this->post('/cotizaciones', [
            'cliente_id' => $cotizacion->cliente_id,
            'porcentaje_descuento' => 3,
        ])
            ->assertSessionHasErrors('porcentaje_descuento');
        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_dos_cotizaciones_en_el_mismo_instante_tienen_folios_distintos(): void
    {
        $this->freezeTime();
        [$cotizacion] = $this->preparar('borrador');
        foreach ([
            1,
            2,
        ] as $intento) {
            $this->post('/cotizaciones', [
                'cliente_id' => $cotizacion->cliente_id,
            ])
                ->assertRedirect('/cotizaciones');
        }
        $this->assertSame(3, Cotizacion::distinct()->count('folio'));
        $this->assertSame('0.00', Cotizacion::latest('id')->first()->porcentaje_descuento);
    }

    public function test_la_advertencia_suma_partidas_del_mismo_producto(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('borrador');
        $pieza->update([
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
        $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'descripcion' => 'Monitor adicional',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);
        $this->get("/cotizaciones/{$cotizacion->id}")->assertOk()
            ->assertSee('Monitor: se cotizaron 2 piezas y hay 1 disponibles.');
    }

    public function test_faltantes_en_partidas_repetidas_no_dejan_reservas_parciales(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('pendiente');
        $pieza->update([
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
        $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'descripcion' => 'Monitor adicional',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);
        $this->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertSessionHas('error');
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('disponible', $pieza->fresh()->estado);
        $this->assertNull($pieza->fresh()->cotizacion_id);
    }

    public function test_no_se_acepta_una_cotizacion_fuera_de_vigencia(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pendiente');
        $cotizacion->update([
            'vence_en' => now()->subSecond(),
        ]);
        $pieza->update([
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
        $this->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertSessionHasErrors('cotizacion');
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('disponible', $pieza->fresh()->estado);
    }

    public function test_el_servicio_no_puede_reservar_dos_veces_una_cotizacion(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('aceptada');
        $adicional = PiezaInventario::create([
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'numero_serie' => 'OTRA-SERIE',
            'estado' => 'disponible',
        ]);
        try {
            app(ServicioInventarioCotizacion::class)->reservar($cotizacion);
            $this->fail('La segunda reserva debió bloquearse.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('cotizacion', $excepcion->errors());
        }
        $this->assertSame('disponible', $adicional->fresh()->estado);
    }

    public function test_el_vencimiento_respeta_una_vigencia_actualizada(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('aceptada');
        $cotizacion->update(['vence_en' => now()->subMinute()]);
        Cotizacion::whereKey($cotizacion->id)->update(['vence_en' => now()->addDays(5)]);

        $liberada = app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true);

        $this->assertFalse($liberada);
        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_una_copia_antigua_no_cancela_una_cotizacion_que_volvio_a_pendiente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('aceptada');
        Cotizacion::whereKey($cotizacion->id)->update(['estado' => 'pendiente']);

        $liberada = app(ServicioInventarioCotizacion::class)->liberar($cotizacion, soloSiVencida: true);

        $this->assertFalse($liberada);
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_el_formulario_muestra_el_error_y_conserva_los_datos(): void
    {
        [$cotizacion] = $this->preparar('borrador');

        $this->from('/cotizaciones')->post('/cotizaciones', [
            'cliente_id' => $cotizacion->cliente_id,
            'area_solicitante' => 'Administración',
            'porcentaje_descuento' => 3,
        ])->assertRedirect('/cotizaciones')->assertSessionHasErrors('porcentaje_descuento');

        $this->get('/cotizaciones')->assertOk()
            ->assertSee('El descuento debe ser 0 (sin descuento) o estar entre 5% y 10%.')
            ->assertSee('value="Administración"', false)
            ->assertSee('value="3"', false);
    }

    public function test_cancelar_relee_el_estado_y_libera_una_aceptacion_reciente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pendiente');
        Cotizacion::whereKey($cotizacion->id)->update(['estado' => 'aceptada']);

        app(ControladorCotizaciones::class)->cancelar($cotizacion);

        $this->assertSame('cancelada', $cotizacion->fresh()->estado);
        $this->assertSame('disponible', $pieza->fresh()->estado);
        $this->assertNull($pieza->fresh()->cotizacion_id);
    }

    public function test_rechazar_no_sobrescribe_una_aceptacion_reciente(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('pendiente');
        Cotizacion::whereKey($cotizacion->id)->update(['estado' => 'aceptada']);

        try {
            app(ControladorCotizaciones::class)->rechazar($cotizacion);
            $this->fail('Debe impedirse el rechazo de una cotización que ya fue aceptada.');
        } catch (HttpException $error) {
            $this->assertSame(422, $error->getStatusCode());
        }

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame('reservada', $pieza->fresh()->estado);
    }

    public function test_no_se_puede_agregar_un_articulo_desactivado_por_solicitud_directa(): void
    {
        [$cotizacion, , $pieza] = $this->preparar('borrador');
        ArticuloCatalogo::whereKey($pieza->articulo_catalogo_id)->update(['activo' => false]);

        $this->post("/cotizaciones/{$cotizacion->id}/partidas", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'cantidad' => 1,
        ])->assertSessionHasErrors('articulo_catalogo_id');

        $this->assertDatabaseCount('partidas_cotizacion', 1);
        $this->assertSame('475.00', $cotizacion->fresh()->total);
    }

    public function test_editar_una_partida_conserva_el_precio_cotizado_aunque_cambie_el_catalogo(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('borrador');
        ArticuloCatalogo::whereKey($pieza->articulo_catalogo_id)->update(['precio' => 900]);

        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'descripcion' => 'Monitor para recepción',
            'cantidad' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('partidas_cotizacion', [
            'id' => $partida->id,
            'descripcion' => 'Monitor para recepción',
            'cantidad' => 2,
            'precio_unitario' => 500,
            'subtotal' => 1000,
        ]);
        $this->assertSame('950.00', $cotizacion->fresh()->total);
    }

    public function test_desactivar_el_articulo_no_bloquea_la_edicion_de_su_partida_historica(): void
    {
        [$cotizacion, $partida, $pieza] = $this->preparar('borrador');
        ArticuloCatalogo::whereKey($pieza->articulo_catalogo_id)->update(['activo' => false]);

        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $pieza->articulo_catalogo_id,
            'descripcion' => 'Monitor conservado en el historial',
            'cantidad' => 2,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('partidas_cotizacion', [
            'id' => $partida->id,
            'descripcion' => 'Monitor conservado en el historial',
            'precio_unitario' => 500,
            'subtotal' => 1000,
        ]);
    }

    private function preparar(string $estado): array
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($usuario);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Ana',
            'rfc' => 'ANA010101AA1',
            'correo' => 'ana@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Monitor',
            'codigo' => 'MON-EDITAR',
            'unidad' => 'pieza',
            'precio' => 500,
            'existencias' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-EDITAR',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'aceptada_en' => now(),
            'porcentaje_descuento' => 5,
            'total' => 475,
        ]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'articulo_catalogo_id' => $articulo->id,
            'descripcion' => 'Monitor',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);
        $pieza = PiezaInventario::create([
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'SERIE-EDITAR',
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);

        return [
            $cotizacion,
            $partida,
            $pieza,
        ];
    }
}
