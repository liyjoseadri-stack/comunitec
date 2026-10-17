<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelOperativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_ve_totales_listas_limitadas_y_enlaces_operativos(): void
    {
        Carbon::setTestNow('2026-10-16 08:00:00'); // Viernes.
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $cliente = $this->crearCliente();

        foreach (range(1, 7) as $indice) {
            $this->crearCotizacion($cliente, $administrador, "ENTREGA-{$indice}", 'aceptada', '2026-10-19 12:00:00');
            Producto::create([
                'nombre' => "Producto bajo {$indice}",
                'codigo' => "BAJO-{$indice}",
                'unidad' => 'pieza',
                'precio' => 100,
                'existencias' => $indice === 1 ? 0 : min($indice, 5),
                'requiere_numero_serie' => false,
                'activo' => true,
            ]);
        }

        $this->crearCotizacion($cliente, $administrador, 'OTRO-DIA', 'aceptada', '2026-10-20 12:00:00');
        $this->crearCotizacion($cliente, $administrador, 'NO-ACEPTADA', 'pendiente', '2026-10-19 12:00:00');
        Producto::create([
            'nombre' => 'Producto suficiente',
            'codigo' => 'SUFICIENTE',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 6,
            'requiere_numero_serie' => false,
            'activo' => true,
        ]);

        $respuesta = $this->actingAs($administrador)->get('/panel');

        $respuesta->assertOk()
            ->assertViewHas('totalEntregasPorVencer', 7)
            ->assertViewHas('entregasPorVencer', fn ($entregas): bool => $entregas->count() === 5)
            ->assertViewHas('totalProductosStockBajo', 7)
            ->assertViewHas('productosStockBajo', fn ($productos): bool => $productos->count() === 5)
            ->assertSee('Mostrando 5 de 7')
            ->assertSee('Agotado')
            ->assertSee('Crítico')
            ->assertSee('Bajo')
            ->assertSee(route('cotizaciones.listado', ['estado' => Cotizacion::ESTADO_ACEPTADA]), false)
            ->assertSee(route('inventario.productos.listado'), false)
            ->assertDontSee('OTRO-DIA')
            ->assertDontSee('NO-ACEPTADA')
            ->assertDontSee('Producto suficiente');
    }

    public function test_el_panel_conserva_la_regla_de_no_avisar_entregas_durante_el_fin_de_semana(): void
    {
        Carbon::setTestNow('2026-10-17 08:00:00'); // Sábado.
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $cliente = $this->crearCliente();
        $this->crearCotizacion($cliente, $administrador, 'ENTREGA-LUNES', 'aceptada', '2026-10-19 12:00:00');

        $this->actingAs($administrador)->get('/panel')
            ->assertOk()
            ->assertViewHas('totalEntregasPorVencer', 0)
            ->assertDontSee('ENTREGA-LUNES');
    }

    public function test_las_alertas_son_exclusivas_del_administrador_y_los_demas_roles_reciben_accesos_utiles(): void
    {
        foreach ([Usuario::ROL_COMERCIAL, Usuario::ROL_CONSULTA] as $rol) {
            $usuario = Usuario::factory()->create(['rol' => $rol]);

            $respuesta = $this->actingAs($usuario)->get('/panel');

            $respuesta->assertOk()
                ->assertViewHas('totalEntregasPorVencer', 0)
                ->assertViewHas('totalProductosStockBajo', 0)
                ->assertDontSee('Entregas del siguiente día hábil')
                ->assertDontSee('Productos con stock bajo')
                ->assertSee(route('cotizaciones.listado'), false)
                ->assertSee(route('ventas.listado'), false)
                ->assertSee(route('reportes.resumen'), false);

            if ($rol === Usuario::ROL_COMERCIAL) {
                $respuesta->assertSee(route('cotizaciones.crear'), false);
            } else {
                $respuesta->assertDontSee(route('cotizaciones.crear'), false);
            }
        }
    }

    private function crearCliente(): Cliente
    {
        return Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente del panel',
            'rfc' => 'CPA010101AA1',
            'correo' => 'panel@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio del panel',
            'codigo_postal' => '29000',
        ]);
    }

    private function crearCotizacion(
        Cliente $cliente,
        Usuario $usuario,
        string $folio,
        string $estado,
        string $entrega
    ): Cotizacion {
        return Cotizacion::create([
            'folio' => $folio.'-'.Str::ulid(),
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'entrega_limite_en' => $entrega,
            'porcentaje_descuento' => 0,
            'total' => 1000,
        ]);
    }
}
