<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormulariosAccesiblesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_modulo_asocia_su_error_al_control_y_conserva_su_valor(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => 'administrador']));
        foreach ([
            ['/mi-perfil', 'nombre-perfil', 'nombre'],
            ['/clientes/crear', 'nombre-cliente', 'nombre'],
            ['/inventario/productos/crear', 'nombre', 'nombre'],
            ['/inventario/servicios/crear', 'nombre', 'nombre'],
            ['/inventario/categorias/crear', 'nombre', 'nombre'],
            ['/cotizaciones/nueva', 'area', 'area_solicitante'],
            ['/administracion/usuarios/crear', 'nombre-usuario', 'nombre'],
            ['/reportes', 'mes-reporte', 'mes'],
        ] as [$ruta, $id, $nombre]) {
            $respuesta = $this->withSession([
                'errors' => $this->errores([$nombre => 'Corrige este dato concreto.']),
                '_old_input' => [$nombre => '2026-09'],
            ])->get($ruta)->assertOk();
            $dom = $this->dom($respuesta->getContent());
            $control = $dom->query('//*[@id="'.$id.'"]')->item(0);
            $this->assertNotNull($control, $ruta);
            $this->assertSame('true', $control->getAttribute('aria-invalid'), $ruta);
            $this->assertSame('2026-09', $control->getAttribute('value'), $ruta);
            $this->assertSame(1, $dom->query('//label[@for="'.$id.'"]')->length, $ruta);
            $this->comprobarReferencias($dom);
        }
    }

    public function test_acceso_asocia_el_error_y_no_rellena_contrasenas(): void
    {
        $respuesta = $this->withSession([
            'errors' => $this->errores(['correo' => 'Las credenciales no son correctas.', 'contrasena' => 'Ingresa la contraseña.']),
            '_old_input' => ['correo' => 'persona@example.test', 'contrasena' => 'no-exponer'],
        ])->get('/iniciar-sesion')->assertOk();
        $dom = $this->dom($respuesta->getContent());
        $this->assertSame('true', $dom->query('//*[@id="correo"]')->item(0)->getAttribute('aria-invalid'));
        $this->assertSame('persona@example.test', $dom->query('//*[@id="correo"]')->item(0)->getAttribute('value'));
        $this->assertFalse($dom->query('//*[@id="contrasena"]')->item(0)->hasAttribute('value'));
        $respuesta->assertDontSee('no-exponer');
        $this->comprobarReferencias($dom);
    }

    public function test_el_componente_genera_ids_unicos_y_combina_ayuda_error_y_descripcion_externa(): void
    {
        $this->app['view']->share('errors', $this->errores(['telefono' => '<b>Teléfono inválido</b>']));
        $html = Blade::render('<p id="instruccion">Contacto</p><x-campo-formulario nombre="telefono" etiqueta="Teléfono" ayuda="Incluye la lada." aria-describedby="instruccion" inputmode="tel" required /><x-campo-formulario nombre="telefono" etiqueta="Otro teléfono" />');
        $dom = $this->dom($html);
        $controles = $dom->query('//input');
        $this->assertNotSame($controles->item(0)->getAttribute('id'), $controles->item(1)->getAttribute('id'));
        $this->assertSame(3, count(explode(' ', $controles->item(0)->getAttribute('aria-describedby'))));
        $this->assertSame('tel', $controles->item(0)->getAttribute('inputmode'));
        $this->assertTrue($controles->item(0)->hasAttribute('required'));
        $this->assertStringNotContainsString('<b>Teléfono inválido</b>', $html);
        $this->comprobarReferencias($dom);
    }

    public function test_las_series_anidadas_conservan_nombre_indice_y_error_individual(): void
    {
        $usuario = Usuario::factory()->create(['rol' => 'administrador']);
        $this->actingAs($usuario);
        $cliente = Cliente::create(['tipo' => 'fisica', 'nombre' => 'Cliente', 'rfc' => 'XAXX010101000', 'correo' => 'cliente@example.test', 'telefono' => '9610000000', 'direccion' => 'Calle', 'codigo_postal' => '29000']);
        $producto = Producto::create(['nombre' => 'Equipo', 'codigo' => 'SER-01', 'unidad' => 'pieza', 'precio' => 100, 'existencias' => 4, 'requiere_numero_serie' => true]);
        $cotizacion = Cotizacion::create(['folio' => 'COT-FORM', 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id, 'estado' => 'aceptada', 'entrega_limite_en' => now()->addDay(), 'porcentaje_descuento' => 0, 'total' => 200]);
        $partida = PartidaCotizacion::create(['cotizacion_id' => $cotizacion->id, 'tipo' => 'producto', 'producto_id' => $producto->id, 'nombre_concepto' => 'Equipo', 'requiere_numero_serie' => true, 'descripcion' => 'Equipo', 'cantidad' => 2, 'precio_unitario' => 100, 'subtotal' => 200]);
        $dom = $this->dom($this->withSession([
            'errors' => $this->errores(['series.'.$partida->id.'.1' => 'Esta serie ya está registrada.']),
            '_old_input' => ['series' => [$partida->id => ['SER-A', 'SER-B']]],
        ])->get('/ventas/registrar/'.$cotizacion->id)->assertOk()->getContent());
        $primero = $dom->query('//*[@id="serie-'.$partida->id.'-0"]')->item(0);
        $segundo = $dom->query('//*[@id="serie-'.$partida->id.'-1"]')->item(0);
        $this->assertSame('series['.$partida->id.'][]', $segundo->getAttribute('name'));
        $this->assertSame('SER-B', $segundo->getAttribute('value'));
        $this->assertSame('true', $segundo->getAttribute('aria-invalid'));
        $this->assertFalse($primero->hasAttribute('aria-invalid'));
        $this->assertTrue($segundo->hasAttribute('required'));
        $this->comprobarReferencias($dom);
    }

    public function test_los_controles_conservan_validacion_nativa_y_teclados_sin_forzar_orden_tab(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => 'administrador']));
        $clientes = $this->dom($this->withSession(['_old_input' => ['tipo' => 'fisica', 'codigo_postal' => '02900']])->get('/clientes/crear')->assertOk()->getContent());
        $this->assertSame('fisica', $clientes->query('//*[@id="tipo-cliente"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('02900', $clientes->query('//*[@id="codigo-postal-cliente"]')->item(0)->getAttribute('value'));
        $this->assertSame('numeric', $clientes->query('//*[@id="codigo-postal-cliente"]')->item(0)->getAttribute('inputmode'));
        $this->assertSame('tel', $clientes->query('//*[@id="telefono-cliente"]')->item(0)->getAttribute('inputmode'));
        $this->assertSame('email', $clientes->query('//*[@id="correo-cliente"]')->item(0)->getAttribute('type'));
        foreach (['nombre-cliente', 'rfc-cliente', 'correo-cliente', 'telefono-cliente', 'codigo-postal-cliente', 'direccion-cliente'] as $id) {
            $control = $clientes->query('//*[@id="'.$id.'"]')->item(0);
            $this->assertTrue($control->hasAttribute('required'));
            $this->assertFalse($control->hasAttribute('aria-invalid'));
            $this->assertFalse($control->hasAttribute('aria-describedby'));
            $this->assertFalse($control->hasAttribute('tabindex'));
        }
        $productos = $this->dom($this->get('/inventario/productos/crear')->assertOk()->getContent());
        $precio = $productos->query('//*[@id="precio"]')->item(0);
        $this->assertSame('number', $precio->getAttribute('type'));
        $this->assertSame('0.01', $precio->getAttribute('step'));
        $this->assertSame('0', $precio->getAttribute('min'));
        $this->assertSame('decimal', $precio->getAttribute('inputmode'));
        $this->assertSame('numeric', $productos->query('//*[@id="existencias"]')->item(0)->getAttribute('inputmode'));
        $this->assertSame(1, $productos->query('//textarea[@id="descripcion" and @name="descripcion"]')->length);
        $this->assertSame(0, $productos->query('//*[@tabindex > 0]')->length);
    }

    public function test_una_respuesta_invalida_muestra_el_mensaje_real_del_validador_en_el_campo(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => 'administrador']));
        $this->from('/clientes/crear')->post('/clientes', [
            'tipo' => 'fisica', 'nombre' => 'Nombre conservado', 'rfc' => 'XAXX010101000',
            'correo' => 'correo-invalido', 'telefono' => '9610000000', 'direccion' => 'Calle', 'codigo_postal' => '02900',
        ])->assertRedirect('/clientes/crear')->assertSessionHasErrors('correo');
        $mensaje = session('errors')->first('correo');
        $dom = $this->dom($this->get('/clientes/crear')->assertOk()->getContent());
        $this->assertSame($mensaje, trim($dom->query('//*[@id="correo-cliente-error"]')->item(0)->textContent));
        $this->assertSame('correo-invalido', $dom->query('//*[@id="correo-cliente"]')->item(0)->getAttribute('value'));
        $this->assertSame('Nombre conservado', $dom->query('//*[@id="nombre-cliente"]')->item(0)->getAttribute('value'));
        $this->comprobarReferencias($dom);

        $this->from('/reportes')->get('/reportes?mes=incorrecto')->assertRedirect('/reportes')->assertSessionHasErrors('mes');
        $respuesta = $this->get('/reportes')->assertOk()->assertSee('Selecciona un mes válido.')->assertDontSee('Revisa los filtros');
        $this->assertSame('Selecciona un mes válido.', trim($this->dom($respuesta->getContent())->query('//*[@id="mes-reporte-error"]')->item(0)->textContent));
    }

    public function test_los_checkboxes_especializados_tambien_asocian_sus_errores(): void
    {
        $dom = $this->dom($this->withSession(['errors' => $this->errores(['recordar' => 'Indica si deseas recordar este equipo.'])])->get('/iniciar-sesion')->assertOk()->getContent());
        $this->assertSame('true', $dom->query('//*[@id="recordar"]')->item(0)->getAttribute('aria-invalid'));
        $this->comprobarReferencias($dom);
        $this->actingAs(Usuario::factory()->create(['rol' => 'administrador']));
        $dom = $this->dom($this->withSession(['errors' => $this->errores(['requiere_numero_serie' => 'Indica si el producto requiere series.'])])->get('/inventario/productos/crear')->assertOk()->getContent());
        $control = $dom->query('//input[@name="requiere_numero_serie"]')->item(0);
        $this->assertSame('true', $control->getAttribute('aria-invalid'));
        $this->assertSame(1, $dom->query('//label[@for="'.$control->getAttribute('id').'"]')->length);
        $this->comprobarReferencias($dom);
    }

    private function errores(array $mensajes): ViewErrorBag
    {
        return (new ViewErrorBag)->put('default', new MessageBag($mensajes));
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($documento);
    }

    private function comprobarReferencias(DOMXPath $dom): void
    {
        $ids = [];
        foreach ($dom->query('//*[@id]') as $nodo) {
            $id = $nodo->getAttribute('id');
            $this->assertNotContains($id, $ids, 'ID duplicado: '.$id);
            $ids[] = $id;
        }
        foreach ($dom->query('//*[@aria-describedby]') as $nodo) {
            foreach (explode(' ', $nodo->getAttribute('aria-describedby')) as $referencia) {
                $this->assertContains($referencia, $ids);
            }
        }
        foreach ($dom->query('//*[@aria-invalid="true"]') as $nodo) {
            $this->assertNotSame('', $nodo->getAttribute('aria-describedby'));
        }
    }
}
