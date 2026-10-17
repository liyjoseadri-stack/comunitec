<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmacionesInterfazTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_layout_comparte_un_dialogo_con_nombre_descripcion_y_acciones_explicitas(): void
    {
        $this->actingAs(Usuario::factory()->create());
        $dom = $this->dom($this->get('/mi-perfil')->assertOk()->getContent());

        $this->assertSame(1, $dom->query('//dialog[@id="dialogo-confirmacion" and @aria-labelledby="confirmacion-titulo" and @aria-describedby="confirmacion-mensaje"]')->length);
        $this->assertSame(1, $dom->query('//*[@id="confirmacion-titulo"]')->length);
        $this->assertSame(1, $dom->query('//*[@id="confirmacion-mensaje"]')->length);
        $this->assertSame(1, $dom->query('//dialog//button[@type="button" and @data-confirmacion-volver and @autofocus]')->length);
        $this->assertSame(1, $dom->query('//dialog//button[@type="button" and @data-confirmacion-aceptar and contains(@class, "boton--peligro")]')->length);
        $this->assertSame(0, $dom->query('//dialog[@open]')->length);
    }

    public function test_solo_desactivar_pide_confirmacion_contextual_y_conserva_los_metodos_y_csrf(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $this->actingAs($administrador);
        $activo = Usuario::factory()->create(['nombre' => 'Usuario <seguro>', 'activo' => true]);
        $inactivo = Usuario::factory()->create(['activo' => false]);
        $categoria = Categoria::create(['nombre' => 'Categoría <segura>', 'activo' => true]);
        $producto = Producto::create(['nombre' => 'Producto <seguro>', 'codigo' => 'CONF-P', 'unidad' => 'pieza', 'precio' => 100, 'existencias' => 1, 'activo' => true]);
        $servicio = Servicio::create(['nombre' => 'Servicio <seguro>', 'codigo' => 'CONF-S', 'unidad' => 'hora', 'precio' => 100, 'activo' => true]);
        $casos = [
            ['/administracion/usuarios', route('administracion.usuarios.estado', $activo), $activo->nombre, 'acceder'],
            ['/inventario/categorias', route('inventario.categorias.estado', $categoria), $categoria->nombre, 'seleccionar'],
            ['/inventario/productos', route('inventario.productos.estado', $producto), $producto->nombre, 'nuevas cotizaciones'],
            ['/inventario/servicios', route('inventario.servicios.estado', $servicio), $servicio->nombre, 'nuevas cotizaciones'],
        ];

        foreach ($casos as [$ruta, $accion, $nombre, $consecuencia]) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $formulario = $dom->query('//form[@action="'.$accion.'"]')->item(0);
            $this->assertTrue($formulario->hasAttribute('data-confirmar'), $ruta);
            $this->assertSame('Desactivar', $formulario->getAttribute('data-confirmar-etiqueta'));
            $this->assertStringContainsString($nombre, $formulario->getAttribute('data-confirmar-mensaje'));
            $this->assertStringContainsString($consecuencia, $formulario->getAttribute('data-confirmar-mensaje'));
            $this->assertSame(1, $dom->query('.//button[@type="submit" and contains(@class, "boton--peligro")]', $formulario)->length);
            $this->assertSame(1, $dom->query('.//input[@name="_token"]', $formulario)->length);
            $this->assertSame(1, $dom->query('.//input[@name="_method" and @value="PATCH"]', $formulario)->length);
            $this->assertSame(0, $dom->query('//form[not(@data-confirmar)]//button[normalize-space(.)="Desactivar"]')->length);
        }

        $dom = $this->dom($this->get('/administracion/usuarios')->getContent());
        $this->assertSame(0, $dom->query('//form[@action="'.route('administracion.usuarios.estado', $inactivo).'" and @data-confirmar]')->length);
        $this->assertSame(0, $dom->query('//form[@action="'.route('administracion.usuarios.estado', $administrador).'"]')->length);

        foreach ([$categoria, $producto, $servicio] as $registro) {
            $registro->update(['activo' => false]);
        }
        foreach (array_slice($casos, 1) as [$ruta]) {
            $dom = $this->dom($this->get($ruta)->getContent());
            $this->assertSame(0, $dom->query('//form[@data-confirmar]')->length, $ruta);
            $this->assertSame(1, $dom->query('//button[normalize-space(.)="Activar" and contains(@class, "boton--exito")]')->length);
        }
    }

    public function test_cancelar_rechazar_y_quitar_muestran_la_consecuencia_del_estado_actual(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $this->actingAs($usuario);
        $cliente = Cliente::create(['tipo' => 'fisica', 'nombre' => 'Cliente prueba', 'rfc' => 'XAXX010101000', 'correo' => 'cliente@example.test', 'telefono' => '9610000000', 'direccion' => 'Domicilio', 'codigo_postal' => '29000']);
        $cotizacion = Cotizacion::create(['folio' => 'COT-CONF', 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id, 'estado' => 'borrador', 'porcentaje_descuento' => 0, 'total' => 100]);
        $partida = $cotizacion->partidas()->create(['tipo' => 'otro', 'descripcion' => 'Concepto <seguro>', 'cantidad' => 1, 'precio_unitario' => 100, 'porcentaje_descuento' => 0, 'subtotal' => 100]);

        foreach (['borrador', 'pendiente', 'aceptada'] as $estado) {
            $cotizacion->update(['estado' => $estado]);
            $dom = $this->dom($this->get('/cotizaciones/'.$cotizacion->id)->assertOk()->getContent());
            $cancelar = $dom->query('//form[@action="'.route('cotizaciones.cancelar', $cotizacion).'"]')->item(0);
            $this->assertTrue($cancelar->hasAttribute('data-confirmar'), $estado);
            $this->assertStringContainsString($cotizacion->folio, $cancelar->getAttribute('data-confirmar-mensaje'));
            $this->assertStringContainsString($estado === 'aceptada' ? 'reservas' : 'cancelada', $cancelar->getAttribute('data-confirmar-mensaje'));
            $this->assertSame(1, $dom->query('.//button[contains(@class, "boton--peligro")]', $cancelar)->length);
            $this->assertSame(1, $dom->query('.//input[@name="_token"]', $cancelar)->length);

            foreach ($dom->query('//form[@data-confirmar]') as $formulario) {
                $this->assertNotSame('', $formulario->getAttribute('data-confirmar-titulo'));
                $this->assertNotSame('', $formulario->getAttribute('data-confirmar-etiqueta'));
                $this->assertSame('post', strtolower($formulario->getAttribute('method')));
            }
            $this->assertSame(0, $dom->query('//form[@data-confirmar and (contains(@action, "/aceptar") or contains(@action, "/enviar") or contains(@action, "/correo"))]')->length);

            if ($estado === 'borrador') {
                $quitar = $dom->query('//form[@action="'.route('cotizaciones.partidas.eliminar', [$cotizacion, $partida]).'" and input[@name="_method" and @value="DELETE"]]')->item(0);
                $this->assertTrue($quitar->hasAttribute('data-confirmar'));
                $this->assertStringContainsString($partida->descripcion, $quitar->getAttribute('data-confirmar-mensaje'));
                $this->assertStringContainsString('total', $quitar->getAttribute('data-confirmar-mensaje'));
                $this->assertSame(1, $dom->query('.//input[@name="_method" and @value="DELETE"]', $quitar)->length);
            }
            if ($estado === 'pendiente') {
                $rechazar = $dom->query('//form[@action="'.route('cotizaciones.rechazar', $cotizacion).'"]')->item(0);
                $this->assertTrue($rechazar->hasAttribute('data-confirmar'));
                $this->assertStringContainsString('rechazada', $rechazar->getAttribute('data-confirmar-mensaje'));
            }
        }

        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]));
        $this->assertSame(0, $this->dom($this->get('/cotizaciones/'.$cotizacion->id)->assertOk()->getContent())->query('//form[@data-confirmar]')->length);
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($documento);
    }
}
