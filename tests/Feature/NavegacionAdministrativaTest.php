<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionAdministrativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ventas_muestra_solo_cotizaciones_aceptadas_pendientes_de_registro(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = $this->crearCliente();
        $this->crearCotizacion($cliente, $usuario, 'COT-LISTA-VENTA', 'aceptada');
        $this->crearCotizacion($cliente, $usuario, 'COT-PENDIENTE', 'pendiente');

        $this->actingAs($usuario)
            ->get(route('ventas.listado'))
            ->assertOk()
            ->assertSee('Cotizaciones aceptadas por registrar')
            ->assertSee('COT-LISTA-VENTA')
            ->assertDontSee('COT-PENDIENTE');
    }

    public function test_inventario_admite_filtro_de_tipo_desde_el_menu(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        Producto::create([

            'nombre' => 'Equipo físico',
            'codigo' => 'EQU-NAV',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 2,
        ]);
        Servicio::create([
            'nombre' => 'Servicio de instalación',
            'codigo' => 'SER-NAV',
            'unidad' => 'servicio',
            'precio' => 200,
        ]);

        $this->actingAs($usuario)
            ->get(route('inventario.servicios.listado'))
            ->assertOk()
            ->assertSee('Servicio de instalación')
            ->assertDontSee('Equipo físico')
            ->assertSee('id="nuevo-servicio"', false)
            ->assertDontSee('Existencias');
    }

    public function test_administrador_recibe_el_shell_lateral_con_todos_los_modulos(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->actingAs($administrador)->get(route('panel'))
            ->assertOk()
            ->assertSee('id="navegacion-lateral"', false)
            ->assertSee('aria-label="Navegación administrativa"', false)
            ->assertSee('Cotizaciones')
            ->assertSee('Ventas')
            ->assertSee('Clientes')
            ->assertSee('Inventario')
            ->assertSee('Reportes')
            ->assertSee('Administración')
            ->assertSee('Mi perfil');
    }

    public function test_el_shell_incluye_los_controles_y_recursos_del_menu_adaptable(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($usuario)->get(route('panel'))
            ->assertOk()
            ->assertSee('css/navegacion.css', false)
            ->assertSee('js/navegacion.js', false)
            ->assertSee('aria-controls="navegacion-lateral"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee(route('cotizaciones.listado').'#nueva-cotizacion', false)
            ->assertSee(route('ventas.listado').'#cotizaciones-aceptadas', false);
    }

    public function test_el_inventario_usa_la_marca_y_el_titulo_superior_sin_repetir_encabezados(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $respuesta = $this->actingAs($usuario)
            ->get(route('inventario.productos.listado'))
            ->assertOk()
            ->assertSee('alt="COMUN&TEC"', false)
            ->assertDontSee('Sistema administrativo')
            ->assertSee('<h1 class="barra-superior__titulo">Productos</h1>', false);

        $contenidoPrincipal = strstr(
            strstr($respuesta->getContent(), '<main'),
            '</main>',
            true
        );

        $this->assertNotFalse($contenidoPrincipal);
        $this->assertStringNotContainsString('<h1', $contenidoPrincipal);
        $this->assertStringNotContainsString('encabezado-pagina', $contenidoPrincipal);
    }

    public function test_las_paginas_muestran_un_solo_titulo_principal_en_la_barra_superior(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        foreach ([route('panel'), route('cotizaciones.listado'), route('clientes.listado')] as $ruta) {
            $respuesta = $this->actingAs($usuario)->get($ruta)->assertOk();
            $html = $respuesta->getContent();
            $contenidoPrincipal = strstr(strstr($html, '<main'), '</main>', true);

            $this->assertSame(1, substr_count($html, '<h1'));
            $this->assertStringContainsString('class="barra-superior__titulo"', $html);
            $this->assertNotFalse($contenidoPrincipal);
            $this->assertStringNotContainsString('<h1', $contenidoPrincipal);
        }
    }

    public function test_consulta_recibe_solo_modulos_de_lectura_y_su_perfil(): void
    {
        $consulta = Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]);

        $this->actingAs($consulta)->get(route('panel'))
            ->assertOk()
            ->assertSee('Cotizaciones')
            ->assertSee('Ventas')
            ->assertSee('Reportes')
            ->assertSee('Mi perfil')
            ->assertDontSee('Clientes')
            ->assertDontSee('Inventario')
            ->assertDontSee('Administración');
    }

    public function test_la_ruta_hija_abre_su_grupo_y_marca_el_enlace_activo(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $respuesta = $this->actingAs($usuario)->get(route('reportes.ventas'));

        $respuesta->assertOk()
            ->assertSee('data-grupo="reportes"', false)
            ->assertSee('data-activo="true"', false)
            ->assertSee('data-enlace="reporte-ventas"', false)
            ->assertSee('href="'.route('reportes.ventas').'"', false);

        $navegacion = strstr(
            strstr($respuesta->getContent(), '<aside id="navegacion-lateral"'),
            '</aside>',
            true
        );

        $this->assertNotFalse($navegacion);
        $this->assertSame(1, substr_count($navegacion, 'aria-current="page"'));
    }

    private function crearCliente(): Cliente
    {
        return Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente navegación',
            'rfc' => 'CNA010101AA1',
            'correo' => 'navegacion@cliente.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
    }

    private function crearCotizacion(
        Cliente $cliente,
        Usuario $usuario,
        string $folio,
        string $estado
    ): Cotizacion {
        return Cotizacion::create([
            'folio' => $folio,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'total' => 100,
        ]);
    }
}
