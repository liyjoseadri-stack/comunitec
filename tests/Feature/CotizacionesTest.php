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
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'moral',
            'name' => 'Cliente SA',
            'rfc' => 'CLI010101AA1',
            'email' => 'cliente@example.com',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Laptop',
            'code' => 'LAP-01',
            'unit' => 'pieza',
            'price' => 1000,
            'stock' => 10,
        ]);

        $this->actingAs($user)->post('/cotizaciones', [
            'customer_id' => $customer->id,
            'discount_percent' => 5,
        ])->assertRedirect('/cotizaciones');

        $quote = Cotizacion::firstOrFail();
        $this->assertSame('draft', $quote->status);

        $this->post("/cotizaciones/{$quote->id}/partidas", [
            'catalog_item_id' => $item->id,
            'type' => 'product',
            'description' => 'Laptop',
            'quantity' => 2,
            'unit_price' => 1000,
        ])->assertRedirect();
        $this->assertSame('1900.00', $quote->fresh()->total);

        $this->post("/cotizaciones/{$quote->id}/enviar")->assertRedirect();
        $this->assertSame('pending', $quote->fresh()->status);
        $this->assertNotNull($quote->fresh()->expires_at);
    }

    public function test_eliminar_una_partida_recalcula_el_total(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Ana',
            'rfc' => 'ANA010101AA1',
            'email' => 'ana@example.com',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-PRUEBA-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'discount_percent' => 5,
            'total' => 1900,
        ]);
        $line = $quote->lines()->create([
            'type' => 'other',
            'description' => 'Instalación',
            'quantity' => 2,
            'unit_price' => 1000,
            'subtotal' => 2000,
        ]);

        $this->actingAs($user)->delete("/cotizaciones/{$quote->id}/partidas/{$line->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('quote_lines', [
            'id' => $line->id,
        ]);
        $this->assertSame('0.00', $quote->fresh()->total);
    }

    public function test_el_comercial_puede_rechazar_una_cotizacion_pendiente(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Luis',
            'rfc' => 'LUI010101AA1',
            'email' => 'luis@example.com',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-PRUEBA-2',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 0,
        ]);

        $this->actingAs($user)->post("/cotizaciones/{$quote->id}/rechazar")->assertRedirect();

        $this->assertSame('rejected', $quote->fresh()->status);
    }

    public function test_el_comando_vence_solo_las_pendientes_fuera_de_plazo(): void
    {
        $user = Usuario::factory()->create();
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Eva',
            'rfc' => 'EVA010101AA1',
            'email' => 'eva@example.com',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $overdue = Cotizacion::create([
            'folio' => 'COT-PRUEBA-3',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
            'total' => 0,
        ]);
        $current = Cotizacion::create([
            'folio' => 'COT-PRUEBA-4',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
            'total' => 0,
        ]);

        $this->artisan('quotes:expire')->assertExitCode(0);

        $this->assertSame('expired', $overdue->fresh()->status);
        $this->assertSame('pending', $current->fresh()->status);
    }

    public function test_el_comercial_puede_descargar_el_pdf(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Marta',
            'rfc' => 'MAR010101AA1',
            'email' => 'marta@example.com',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-PDF-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'total' => 0,
        ]);

        $this->actingAs($user)->get("/cotizaciones/{$quote->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_los_estados_se_muestran_en_espanol(): void
    {
        $quote = new Cotizacion([
            'status' => 'pending',
        ]);

        $this->assertSame('Pendiente', $quote->etiquetaEstado());
        $quote->status = 'expired';
        $this->assertSame('Vencida', $quote->etiquetaEstado());
    }

    public function test_el_comercial_puede_enviar_la_cotizacion_por_correo(): void
    {
        Mail::fake();
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'moral',
            'name' => 'Empresa cliente',
            'rfc' => 'EMP010101AA1',
            'email' => 'contacto@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-CORREO-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 0,
        ]);

        $this->actingAs($user)->post("/cotizaciones/{$quote->id}/correo")->assertRedirect();

        Mail::assertSent(CorreoCotizacion::class, function (CorreoCotizacion $mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    public function test_aceptar_la_cotizacion_reserva_sus_piezas(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'moral',
            'name' => 'Empresa cliente',
            'rfc' => 'EMP010101AA1',
            'email' => 'contacto@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Cámara',
            'code' => 'CAM-01',
            'unit' => 'pieza',
            'price' => 500,
            'stock' => 2,
        ]);
        $unit = PiezaInventario::create([
            'catalog_item_id' => $item->id,
            'serial_number' => 'CAM-001',
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-RESERVA-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 500,
        ]);
        $quote->lines()->create([
            'catalog_item_id' => $item->id,
            'type' => 'product',
            'description' => 'Cámara',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);

        $this->actingAs($user)->post("/cotizaciones/{$quote->id}/aceptar")->assertRedirect();

        $this->assertSame('accepted', $quote->fresh()->status);
        $this->assertDatabaseHas('inventory_units', [
            'id' => $unit->id,
            'status' => 'reserved',
            'quote_id' => $quote->id,
        ]);
    }

    public function test_la_aceptacion_se_bloquea_con_alerta_si_faltan_piezas(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Pablo',
            'rfc' => 'PAB010101AA1',
            'email' => 'pablo@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Router',
            'code' => 'ROU-01',
            'unit' => 'pieza',
            'price' => 500,
            'stock' => 0,
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-RESERVA-2',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 500,
        ]);
        $quote->lines()->create([
            'catalog_item_id' => $item->id,
            'type' => 'product',
            'description' => 'Router',
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);

        $this->actingAs($user)->post("/cotizaciones/{$quote->id}/aceptar")
            ->assertRedirect()
            ->assertSessionHas('error', 'No hay piezas disponibles suficientes para aceptar la cotización.');

        $this->assertSame('pending', $quote->fresh()->status);
    }

    public function test_cancelar_una_aceptada_libera_las_reservas(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Rosa',
            'rfc' => 'ROS010101AA1',
            'email' => 'rosa@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Monitor',
            'code' => 'MON-01',
            'unit' => 'pieza',
            'price' => 500,
            'stock' => 1,
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-LIBERAR-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'total' => 500,
        ]);
        $unit = PiezaInventario::create([
            'catalog_item_id' => $item->id,
            'serial_number' => 'MON-001',
            'status' => 'reserved',
            'quote_id' => $quote->id,
        ]);

        $this->actingAs($user)->post("/cotizaciones/{$quote->id}/cancelar")->assertRedirect();

        $this->assertSame('cancelled', $quote->fresh()->status);
        $this->assertDatabaseHas('inventory_units', [
            'id' => $unit->id,
            'status' => 'available',
            'quote_id' => null,
        ]);
    }

    public function test_el_comando_cancela_aceptadas_vencidas_y_libera_piezas(): void
    {
        $user = Usuario::factory()->create();
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Hugo',
            'rfc' => 'HUG010101AA1',
            'email' => 'hugo@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Teclado',
            'code' => 'TEC-01',
            'unit' => 'pieza',
            'price' => 300,
            'stock' => 1,
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-VENCER-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'expires_at' => now()->subMinute(),
            'total' => 300,
        ]);
        $unit = PiezaInventario::create([
            'catalog_item_id' => $item->id,
            'serial_number' => 'TEC-001',
            'status' => 'reserved',
            'quote_id' => $quote->id,
        ]);

        $this->artisan('quotes:expire')->assertExitCode(0);

        $this->assertSame('cancelled', $quote->fresh()->status);
        $this->assertDatabaseHas('inventory_units', [
            'id' => $unit->id,
            'status' => 'available',
            'quote_id' => null,
        ]);
    }

    public function test_el_borrador_advierte_faltantes_sin_bloquear_su_creacion(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Nora',
            'rfc' => 'NOR010101AA1',
            'email' => 'nora@cliente.test',
            'phone' => '9610000000',
            'address' => 'Dirección 1',
            'postal_code' => '29000',
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Switch',
            'code' => 'SWI-01',
            'unit' => 'pieza',
            'price' => 500,
            'stock' => 0,
        ]);
        $quote = Cotizacion::create([
            'folio' => 'COT-AVISO-1',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'total' => 1000,
        ]);
        $quote->lines()->create([
            'catalog_item_id' => $item->id,
            'type' => 'product',
            'description' => 'Switch',
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
        ]);

        $this->actingAs($user)->get("/cotizaciones/{$quote->id}")
            ->assertOk()
            ->assertSee('Advertencia de inventario')
            ->assertSee('Switch: se cotizaron 2 piezas y hay 0 disponibles.');
    }

    public function test_una_partida_de_catalogo_toma_el_precio_desde_el_servidor(): void
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'type' => 'moral',
            'name' => 'Cliente protegido',
            'rfc' => 'CPR010101AA1',
            'email' => 'cliente@prueba.test',
            'phone' => '9610000000',
            'address' => 'Dirección de prueba',
            'postal_code' => '29000',
        ]);
        $articulo = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Equipo de cómputo',
            'code' => 'EQ-PROTEGIDO',
            'unit' => 'pieza',
            'price' => 1250,
            'stock' => 1,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PRECIO-SERVIDOR',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'draft',
            'discount_percent' => 0,
            'total' => 0,
        ]);

        $this->actingAs($usuario)->post("/cotizaciones/{$cotizacion->id}/partidas", [
            'type' => 'product',
            'catalog_item_id' => $articulo->id,
            'description' => 'Equipo para el área administrativa',
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('quote_lines', [
            'quote_id' => $cotizacion->id,
            'description' => 'Equipo para el área administrativa',
            'quantity' => 2,
            'unit_price' => 1250,
            'subtotal' => 2500,
        ]);
        $this->assertSame('2500.00', $cotizacion->fresh()->total);
    }
}
