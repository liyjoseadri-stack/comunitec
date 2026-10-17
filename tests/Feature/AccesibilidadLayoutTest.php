<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\Venta;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesibilidadLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_modulos_comparten_un_salto_al_contenido_y_un_main_enfocable(): void
    {
        foreach ($this->rutasAdministrativas() as $ruta) {
            $respuesta = $this->get($ruta)->assertOk();
            $dom = $this->dom($respuesta->getContent());

            $this->assertSame(1, $dom->query('//a[@class="saltar-contenido" and @href="#contenido-principal"]')->length, $ruta);
            $this->assertSame(1, $dom->query('//main[@id="contenido-principal" and @tabindex="-1"]')->length, $ruta);
            $this->assertSame('Saltar al contenido', trim($dom->query('//body/a[1]')->item(0)?->textContent ?? ''), $ruta);
        }
    }

    #[DataProvider('mensajesFlash')]
    public function test_cada_flash_se_muestra_una_sola_vez_en_todos_los_modulos(string $clave, string $rol, string $variante): void
    {
        foreach ($this->rutasAdministrativas() as $ruta) {
            $respuesta = $this->withSession([$clave => 'Mensaje único de prueba'])->get($ruta)->assertOk();
            $dom = $this->dom($respuesta->getContent());
            $alertas = $dom->query('//*[contains(concat(" ", normalize-space(@class), " "), " alerta-flash ")]');

            $this->assertSame(1, $alertas->length, $ruta);
            $this->assertSame($rol, $alertas->item(0)->getAttribute('role'), $ruta);
            $this->assertStringContainsString('alerta--'.$variante, $alertas->item(0)->getAttribute('class'), $ruta);
            $this->assertSame(1, substr_count($respuesta->getContent(), 'Mensaje único de prueba'), $ruta);
        }
    }

    public static function mensajesFlash(): array
    {
        return [
            'estado' => ['estado', 'status', 'exito'],
            'success' => ['success', 'status', 'exito'],
            'error' => ['error', 'alert', 'peligro'],
            'advertencia' => ['advertencia', 'alert', 'advertencia'],
        ];
    }

    public function test_los_flashes_simultaneos_se_conservan_y_escapan_el_contenido(): void
    {
        $this->actingAs(Usuario::factory()->create());
        $respuesta = $this->withSession([
            'estado' => 'Perfil guardado.',
            'success' => 'Operación completada.',
            'error' => '<script>alert("mensaje")</script>',
            'advertencia' => 'Revisa las existencias.',
        ])->get('/mi-perfil')->assertOk()
            ->assertSee('Perfil guardado.')
            ->assertSee('Operación completada.')
            ->assertSee('Revisa las existencias.')
            ->assertSee('<script>alert("mensaje")</script>')
            ->assertDontSee('<script>alert("mensaje")</script>', false);

        $this->assertSame(4, $this->dom($respuesta->getContent())->query('//*[contains(@class, "alerta-flash")]')->length);
    }

    public function test_no_hay_alertas_flash_ni_resumen_de_validacion_sin_mensajes(): void
    {
        $this->actingAs(Usuario::factory()->create());
        $respuesta = $this->get('/mi-perfil')->assertOk();
        $dom = $this->dom($respuesta->getContent());

        $this->assertSame(0, $dom->query('//*[contains(@class, "alerta-flash")]')->length);
        $this->assertSame(0, $dom->query('//*[@id="resumen-errores-validacion"]')->length);
    }

    public function test_los_errores_se_muestran_en_un_solo_resumen_enfocable_con_titulo_asociado(): void
    {
        foreach ($this->rutasAdministrativas() as $ruta) {
            $errores = (new ViewErrorBag)->put('default', new MessageBag([
                'correo' => 'El correo debe ser válido.',
                'nombre' => 'El nombre es obligatorio.',
            ]));
            $respuesta = $this->withSession(['errors' => $errores])->get($ruta)->assertOk();
            $dom = $this->dom($respuesta->getContent());

            $this->assertSame(1, $dom->query('//*[@id="resumen-errores-validacion" and @tabindex="-1" and @role="alert" and @aria-labelledby="titulo-errores-validacion"]')->length, $ruta);
            $this->assertSame(1, $dom->query('//*[@id="titulo-errores-validacion"]')->length, $ruta);
            $respuesta->assertSee('Revisa los datos ingresados')->assertDontSee('Revisa los filtros');
            $resumen = $dom->query('//*[@id="resumen-errores-validacion"]')->item(0)->textContent;
            $this->assertSame(1, substr_count($resumen, 'El correo debe ser válido.'), $ruta);
            $this->assertSame(1, substr_count($resumen, 'El nombre es obligatorio.'), $ruta);
        }
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($documento);
    }

    private function rutasAdministrativas(): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $this->actingAs($usuario);
        $cliente = Cliente::create([
            'tipo' => 'fisica', 'nombre' => 'Cliente de prueba', 'rfc' => 'XAXX010101000',
            'correo' => 'cliente@example.test', 'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba', 'codigo_postal' => '29000',
        ]);
        $categoria = Categoria::create(['nombre' => 'Categoría de prueba']);
        $producto = Producto::create(['nombre' => 'Equipo', 'codigo' => 'ACC-01', 'unidad' => 'pieza', 'precio' => 100, 'existencias' => 10]);
        $servicio = Servicio::create(['nombre' => 'Instalación', 'codigo' => 'ACC-02', 'unidad' => 'hora', 'precio' => 100]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ACC', 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id,
            'estado' => Cotizacion::ESTADO_ACEPTADA, 'porcentaje_descuento' => 0, 'total' => 100,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-ACC', 'cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id,
            'tipo_cliente' => $cliente->tipo, 'nombre_cliente' => $cliente->nombre,
            'rfc_cliente' => $cliente->rfc, 'correo_cliente' => $cliente->correo,
            'telefono_cliente' => $cliente->telefono, 'direccion_cliente' => $cliente->direccion,
            'codigo_postal_cliente' => $cliente->codigo_postal, 'nombre_responsable' => $usuario->nombre,
            'correo_responsable' => $usuario->correo,
            'usuario_id' => $usuario->id, 'vendida_en' => now(), 'metodo_pago' => Venta::METODO_EFECTIVO,
            'subtotal' => 100, 'porcentaje_descuento' => 0, 'total' => 100,
        ]);

        return [
            '/panel', '/clientes', '/inventario/productos', '/inventario/servicios', '/inventario/categorias',
            '/inventario/productos/'.$producto->id, '/inventario/servicios/'.$servicio->id,
            '/inventario/categorias/'.$categoria->id, '/cotizaciones', '/cotizaciones/nueva',
            '/cotizaciones/'.$cotizacion->id, '/ventas', '/ventas/'.$venta->id,
            '/reportes', '/reportes/cotizaciones', '/reportes/ventas', '/administracion/usuarios', '/mi-perfil',
        ];
    }
}
