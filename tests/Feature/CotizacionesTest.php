<?php

namespace Tests\Feature;

use App\Mail\CorreoCotizacion;
use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comercial_crea_un_borrador_agrega_una_partida_y_envia_la_cotizacion(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente SA',
            'rfc' => 'CLI010101AA1',
            'correo' => 'cliente@example.com',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Laptop',
            'codigo' => 'LAP-01',
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 10,
        ]);

        $this->actingAs($usuario)->post('/cotizaciones', [
            'cliente_id' => $cliente->id,
            'porcentaje_descuento' => 5,
        ])->assertRedirect('/cotizaciones');

        $cotizacion = Cotizacion::firstOrFail();
        $this->assertSame('borrador', $cotizacion->estado);

        $this->post("/cotizaciones/{$cotizacion->id}/partidas", [
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Laptop',
            'cantidad' => 2,
            'precio_unitario' => 1000,
        ])->assertRedirect();
        $this->assertSame('1900.00', $cotizacion->fresh()->total);

        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect();
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertNotNull($cotizacion->fresh()->vence_en);
    }

    public function test_eliminar_una_partida_recalcula_el_total(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Ana',
            'rfc' => 'ANA010101AA1',
            'correo' => 'ana@example.com',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PRUEBA-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'porcentaje_descuento' => 5,
            'total' => 1900,
        ]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Instalación',
            'cantidad' => 2,
            'precio_unitario' => 1000,
            'subtotal' => 2000,
        ]);

        $this->actingAs($usuario)->delete("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('partidas_cotizacion', [
            'id' => $partida->id,
        ]);
        $this->assertSame('0.00', $cotizacion->fresh()->total);
    }

    public function test_el_comercial_puede_rechazar_una_cotizacion_pendiente(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Luis',
            'rfc' => 'LUI010101AA1',
            'correo' => 'luis@example.com',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PRUEBA-2',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'total' => 0,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/rechazar")->assertRedirect();

        $this->assertSame('rechazada', $cotizacion->fresh()->estado);
    }

    public function test_el_comando_vence_solo_las_pendientes_fuera_de_plazo(): void
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Eva',
            'rfc' => 'EVA010101AA1',
            'correo' => 'eva@example.com',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $vencida = Cotizacion::create([
            'folio' => 'COT-PRUEBA-3',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'vence_en' => now()->subDay(),
            'total' => 0,
        ]);
        $vigente = Cotizacion::create([
            'folio' => 'COT-PRUEBA-4',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'vence_en' => now()->addDay(),
            'total' => 0,
        ]);

        $this->artisan('cotizaciones:vencer')->assertExitCode(0);

        $this->assertSame('vencida', $vencida->fresh()->estado);
        $this->assertSame('pendiente', $vigente->fresh()->estado);
    }

    public function test_el_comercial_puede_descargar_el_pdf(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Marta',
            'rfc' => 'MAR010101AA1',
            'correo' => 'marta@example.com',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PDF-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'total' => 0,
        ]);
        $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Configuración especializada',
            'cantidad' => 2,
            'precio_unitario' => 750,
            'subtotal' => 1500,
        ]);
        $cotizacion->update([
            'porcentaje_descuento' => 5,
            'total' => 1425,
        ]);

        $this->actingAs($usuario)->get("/cotizaciones/{$cotizacion->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $contenido = view('cotizaciones.pdf', [
            'cotizacion' => $cotizacion->load('cliente', 'partidas'),
        ])->render();

        $this->assertStringContainsString('COT-PDF-1', $contenido);
        $this->assertStringContainsString('Marta', $contenido);
        $this->assertStringContainsString('Configuración especializada', $contenido);
        $this->assertStringContainsString('CARACTERÍSTICAS', $contenido);
        $this->assertStringContainsString('class="totales"', $contenido);
        $this->assertStringContainsString('SUBTOTAL:', $contenido);
        $this->assertStringContainsString('DESCUENTO (5.00%):', $contenido);
        $this->assertStringContainsString('-$75.00', $contenido);
        $this->assertStringContainsString('IVA:', $contenido);
        $this->assertStringNotContainsString('<table class="resumen">', $contenido);
        $this->assertStringContainsString('Tiempo de entrega: 5 días hábiles', $contenido);
        $this->assertStringContainsString('L.I. RICARDO VELÁZQUEZ HERNÁNDEZ', $contenido);
        $this->assertStringContainsString('servicios.comunitec@gmail.com', $contenido);
        $this->assertStringContainsString('nube-comunitec-marca-agua-pdf.jpg', $contenido);
        $this->assertStringContainsString('$1,425.00', $contenido);

        $cotizacion->update([
            'porcentaje_descuento' => 0,
            'total' => 1500,
        ]);

        $contenidoSinDescuento = view('cotizaciones.pdf', [
            'cotizacion' => $cotizacion->fresh()->load('cliente', 'partidas'),
        ])->render();

        $this->assertStringContainsString('DESCUENTO (0.00%):', $contenidoSinDescuento);
        $this->assertStringContainsString('<span class="valor-total">$0.00</span>', $contenidoSinDescuento);
        $this->assertStringNotContainsString('-$0.00', $contenidoSinDescuento);
    }

    public function test_los_estados_se_muestran_en_espanol(): void
    {
        $cotizacion = new Cotizacion([
            'estado' => 'pendiente',
        ]);

        $this->assertSame('Pendiente', $cotizacion->etiquetaEstado());
        $cotizacion->estado = 'vencida';
        $this->assertSame('Vencida', $cotizacion->etiquetaEstado());
    }

    public function test_el_comercial_puede_enviar_la_cotizacion_por_correo(): void
    {
        Mail::fake();
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Empresa cliente',
            'rfc' => 'EMP010101AA1',
            'correo' => 'contacto@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-CORREO-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'total' => 0,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/correo")->assertRedirect();

        Mail::assertSent(CorreoCotizacion::class, function (CorreoCotizacion $mail) use ($cliente) {
            return $mail->hasTo($cliente->correo);
        });
    }

    public function test_aceptar_la_cotizacion_reserva_sus_piezas(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Empresa cliente',
            'rfc' => 'EMP010101AA1',
            'correo' => 'contacto@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Cámara',
            'codigo' => 'CAM-01',
            'unidad' => 'pieza',
            'precio' => 500,
            'existencias' => 2,
        ]);
        $pieza = PiezaInventario::create([
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'CAM-001',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-RESERVA-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'total' => 500,
        ]);
        $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Cámara',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertRedirect();

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertDatabaseHas('piezas_inventario', [
            'id' => $pieza->id,
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);
    }

    public function test_la_aceptacion_se_bloquea_con_alerta_si_faltan_piezas(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Pablo',
            'rfc' => 'PAB010101AA1',
            'correo' => 'pablo@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Router',
            'codigo' => 'ROU-01',
            'unidad' => 'pieza',
            'precio' => 500,
            'existencias' => 0,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-RESERVA-2',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente',
            'total' => 500,
        ]);
        $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Router',
            'cantidad' => 1,
            'precio_unitario' => 500,
            'subtotal' => 500,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/aceptar")
            ->assertRedirect()
            ->assertSessionHas('error', 'No hay piezas disponibles suficientes para aceptar la cotización.');

        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
    }

    public function test_cancelar_una_aceptada_libera_las_reservas(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Rosa',
            'rfc' => 'ROS010101AA1',
            'correo' => 'rosa@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Monitor',
            'codigo' => 'MON-01',
            'unidad' => 'pieza',
            'precio' => 500,
            'existencias' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-LIBERAR-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'total' => 500,
        ]);
        $pieza = PiezaInventario::create([
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'MON-001',
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/cancelar")->assertRedirect();

        $this->assertSame('cancelada', $cotizacion->fresh()->estado);
        $this->assertDatabaseHas('piezas_inventario', [
            'id' => $pieza->id,
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
    }

    public function test_el_comando_cancela_aceptadas_vencidas_y_libera_piezas(): void
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Hugo',
            'rfc' => 'HUG010101AA1',
            'correo' => 'hugo@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Teclado',
            'codigo' => 'TEC-01',
            'unidad' => 'pieza',
            'precio' => 300,
            'existencias' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-VENCER-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'vence_en' => now()->subMinute(),
            'total' => 300,
        ]);
        $pieza = PiezaInventario::create([
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'TEC-001',
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);

        $this->artisan('cotizaciones:vencer')->assertExitCode(0);

        $this->assertSame('cancelada', $cotizacion->fresh()->estado);
        $this->assertDatabaseHas('piezas_inventario', [
            'id' => $pieza->id,
            'estado' => 'disponible',
            'cotizacion_id' => null,
        ]);
    }

    public function test_el_borrador_advierte_faltantes_sin_bloquear_su_creacion(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Nora',
            'rfc' => 'NOR010101AA1',
            'correo' => 'nora@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección 1',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Switch',
            'codigo' => 'SWI-01',
            'unidad' => 'pieza',
            'precio' => 500,
            'existencias' => 0,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-AVISO-1',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'total' => 1000,
        ]);
        $cotizacion->partidas()->create([
            'articulo_catalogo_id' => $articulo->id,
            'tipo' => 'producto',
            'descripcion' => 'Switch',
            'cantidad' => 2,
            'precio_unitario' => 500,
            'subtotal' => 1000,
        ]);

        $this->actingAs($usuario)->get("/cotizaciones/{$cotizacion->id}")
            ->assertOk()
            ->assertSee('Advertencia de inventario')
            ->assertSee('Switch: se cotizaron 2 piezas y hay 0 disponibles.');
    }

    public function test_una_partida_de_catalogo_toma_el_precio_desde_el_servidor(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente protegido',
            'rfc' => 'CPR010101AA1',
            'correo' => 'cliente@prueba.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Equipo de cómputo',
            'codigo' => 'EQ-PROTEGIDO',
            'unidad' => 'pieza',
            'precio' => 1250,
            'existencias' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PRECIO-SERVIDOR',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'porcentaje_descuento' => 0,
            'total' => 0,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/partidas", [
            'tipo' => 'producto',
            'articulo_catalogo_id' => $articulo->id,
            'descripcion' => 'Equipo para el área administrativa',
            'cantidad' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('partidas_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'Equipo para el área administrativa',
            'cantidad' => 2,
            'precio_unitario' => 1250,
            'subtotal' => 2500,
        ]);
        $this->assertSame('2500.00', $cotizacion->fresh()->total);
    }
}
