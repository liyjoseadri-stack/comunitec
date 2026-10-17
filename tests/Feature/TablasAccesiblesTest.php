<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Services\ServicioConversionVenta;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class TablasAccesiblesTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_listados_vacios_comparten_explicacion_y_una_accion_util(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => 'administrador']));
        foreach ([
            ['/clientes', 'Aún no hay clientes registrados.', route('clientes.crear')],
            ['/cotizaciones', 'Aún no hay cotizaciones registradas.', route('cotizaciones.crear')],
            ['/ventas', 'Aún no hay ventas registradas. Primero debe aceptarse y convertirse una cotización.', route('cotizaciones.listado', ['estado' => 'aceptada'])],
            ['/inventario/productos', 'No hay productos con estos filtros.', route('inventario.productos.crear')],
            ['/inventario/servicios', 'No hay servicios con estos filtros.', route('inventario.servicios.crear')],
            ['/inventario/categorias', 'No hay categorías con estos filtros.', route('inventario.categorias.crear')],
            ['/reportes/cotizaciones', 'No hay cotizaciones con estos filtros.', route('reportes.cotizaciones')],
            ['/reportes/ventas', 'No hay ventas con estos filtros.', route('reportes.ventas')],
        ] as [$ruta, $mensaje, $destino]) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $vacios = $dom->query('//*[contains(concat(" ", normalize-space(@class), " "), " estado-vacio ")]');
            $encontrado = false;
            foreach ($vacios as $vacio) {
                if (str_contains($vacio->textContent, $mensaje)) {
                    $this->assertSame(1, $dom->query('.//p[contains(@class, "estado-vacio__mensaje")]', $vacio)->length, $ruta);
                    $this->assertSame(1, $dom->query('.//a[@href="'.$destino.'"]', $vacio)->length, $ruta);
                    $encontrado = true;
                }
            }
            $this->assertTrue($encontrado, $ruta);
            if (str_starts_with($ruta, '/reportes/')) {
                $this->assertSame(0, $dom->query('//p[contains(@class, "tabla-responsive__instruccion")]')->length, $ruta);
                $this->assertSame(1, $dom->query('//*[contains(@class, "tabla-responsive--vacia")]')->length, $ruta);
            }
        }
        $dom = $this->dom((string) $this->view('administracion.usuarios.listado', [
            'usuarios' => new LengthAwarePaginator([], 0, 15),
        ]));
        $this->assertSame(1, $dom->query('//td[@colspan="5"]//a[@href="'.route('administracion.usuarios.crear').'"]')->length);
    }

    public function test_todas_las_columnas_y_las_filas_identificables_tienen_scope(): void
    {
        [$categoria, $cotizacion, $venta] = $this->datos();
        foreach ([
            '/clientes', '/cotizaciones', '/ventas', '/inventario/productos', '/inventario/servicios',
            '/inventario/categorias', '/inventario/categorias/'.$categoria->id,
            '/administracion/usuarios', '/reportes/cotizaciones', '/reportes/ventas',
            '/cotizaciones/'.$cotizacion->id, '/ventas/registrar/'.$cotizacion->id, '/ventas/'.$venta->id,
        ] as $ruta) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $this->assertGreaterThan(0, $dom->query('//table')->length, $ruta);
            $this->assertSame(0, $dom->query('//thead//th[not(@scope="col")]')->length, $ruta);
            foreach ($dom->query('//table[contains(@class, "tabla-registros")]//tbody/tr[not(td[@colspan])]') as $fila) {
                $this->assertSame(1, $dom->query('./th[@scope="row"]', $fila)->length, $ruta);
            }
            $this->assertSame(0, $dom->query('//table[contains(@class, "resumen-importes")]//th[not(@scope="row")]')->length, $ruta);
        }
    }

    public function test_panel_resumen_y_detalles_vacios_usan_el_componente_y_acciones_permitidas(): void
    {
        [, $cotizacion] = $this->datos();
        $vacia = Cotizacion::create([
            'folio' => 'COT-SIN-CONCEPTOS',
            'cliente_id' => $cotizacion->cliente_id,
            'usuario_id' => auth()->id(),
            'estado' => 'borrador',
            'porcentaje_descuento' => 0,
            'total' => 0,
        ]);
        $dom = $this->dom($this->get('/cotizaciones/'.$vacia->id)->assertOk()->getContent());
        $this->assertSame(1, $dom->query('//td[@colspan="8"]//*[contains(@class, "estado-vacio")]//a[@href="#tipo-partida"]')->length);
        $this->assertSame(0, $dom->query('//p[contains(@class, "tabla-responsive__instruccion")]')->length);
        $categoriaVacia = Categoria::create(['nombre' => 'Sin artículos']);
        $dom = $this->dom($this->get('/inventario/categorias/'.$categoriaVacia->id)->assertOk()->getContent());
        $this->assertSame(2, $dom->query('//td[@colspan="3"]//*[contains(@class, "estado-vacio__acciones")]//a')->length);
        Producto::query()->update(['existencias' => 10]);
        $this->travelTo(now()->addYear());
        foreach (['/panel', '/reportes'] as $ruta) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $this->assertSame(2, $dom->query('//p[contains(@class, "estado-vacio__mensaje")]')->length, $ruta);
        }
        $this->actingAs(Usuario::factory()->create(['rol' => 'consulta']));
        $dom = $this->dom($this->get('/cotizaciones/'.$vacia->id)->assertOk()->getContent());
        $this->assertSame(0, $dom->query('//td[@colspan="8"]//a')->length);
        $this->assertSame(1, $dom->query('//td[@colspan="8"]//p[contains(@class, "estado-vacio__mensaje")]')->length);
    }

    public function test_los_listados_adaptables_conservan_cada_columna_y_su_etiqueta_movil(): void
    {
        [$categoria] = $this->datos();
        foreach (['/clientes', '/cotizaciones', '/ventas', '/inventario/productos', '/inventario/servicios', '/inventario/categorias', '/inventario/categorias/'.$categoria->id, '/administracion/usuarios', '/reportes/cotizaciones', '/reportes/ventas'] as $ruta) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $tablas = $dom->query('//table[contains(@class, "tabla-adaptable")]');
            $this->assertGreaterThan(0, $tablas->length, $ruta);
            foreach ($tablas as $tabla) {
                $cabeceras = $dom->query('./thead/tr/th', $tabla);
                foreach ($dom->query('./tbody/tr[not(td[@colspan])]', $tabla) as $fila) {
                    $celdas = $dom->query('./td | ./th', $fila);
                    $this->assertSame($cabeceras->length, $celdas->length, $ruta);
                    foreach ($celdas as $indice => $celda) {
                        $this->assertSame(trim($cabeceras->item($indice)->textContent), $celda->getAttribute('data-etiqueta'), $ruta);
                    }
                }
                $this->assertSame(0, $dom->query('ancestor::*[contains(@class, "tabla-responsive")][1]//p[contains(@class, "tabla-responsive__instruccion")]', $tabla)->length, $ruta);
            }
        }
    }

    public function test_las_tablas_tecnicas_tienen_region_enfocable_e_instruccion_vinculada(): void
    {
        [, $cotizacion, $venta] = $this->datos();
        foreach (['/cotizaciones/'.$cotizacion->id, '/ventas/registrar/'.$cotizacion->id, '/ventas/'.$venta->id, '/inventario/productos'] as $ruta) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $regiones = $dom->query('//*[contains(@class, "tabla-responsive--tecnica")]');
            $this->assertGreaterThan(0, $regiones->length, $ruta);
            foreach ($regiones as $region) {
                $this->assertSame('region', $region->getAttribute('role'), $ruta);
                $this->assertSame('0', $region->getAttribute('tabindex'), $ruta);
                $this->assertNotEmpty($region->getAttribute('aria-label'), $ruta);
                $referencia = $region->getAttribute('aria-describedby');
                $this->assertNotEmpty($referencia, $ruta);
                $this->assertSame(1, $dom->query('//*[@id="'.$referencia.'" and contains(@class, "tabla-responsive__instruccion")]')->length, $ruta);
            }
        }
    }

    public function test_las_acciones_de_fila_conservan_permisos_y_el_estado_vacio_no_expone_escrituras_a_consulta(): void
    {
        [, $cotizacion] = $this->datos();
        $dom = $this->dom($this->get('/administracion/usuarios')->assertOk()->getContent());
        $this->assertGreaterThan(0, $dom->query('//*[contains(@class, "grupo-acciones-fila")]//form')->length);
        $this->assertSame(0, $dom->query('//form[@action="'.route('administracion.usuarios.estado', auth()->user()).'"]')->length);
        foreach (['/inventario/productos', '/inventario/servicios', '/inventario/categorias', '/cotizaciones', '/ventas', '/reportes/cotizaciones', '/reportes/ventas'] as $ruta) {
            $dom = $this->dom($this->get($ruta)->assertOk()->getContent());
            $this->assertGreaterThan(0, $dom->query('//*[contains(@class, "grupo-acciones-fila")]//a')->length, $ruta);
        }
        $this->actingAs(Usuario::factory()->create(['rol' => 'consulta']));
        $this->get('/cotizaciones?estado=cancelada')->assertOk()->assertDontSee('Crear cotización');
        $dom = $this->dom($this->get('/cotizaciones/'.$cotizacion->id)->assertOk()->getContent());
        $this->assertSame(0, $dom->query('//table//form')->length);
        $this->get('/ventas')->assertOk()->assertDontSee('Registrar venta');
        $this->get('/inventario/productos')->assertForbidden();
        $this->get('/administracion/usuarios')->assertForbidden();
    }

    public function test_los_componentes_escapan_mensajes_y_generan_descripciones_unicas_solo_para_tablas_tecnicas(): void
    {
        $html = Blade::render('<x-estado-vacio :mensaje="$mensaje"><x-slot:acciones><a href="#alta">Agregar</a></x-slot:acciones></x-estado-vacio><x-tabla-responsive etiqueta="Detalle"><table></table></x-tabla-responsive><x-tabla-responsive etiqueta="Otro detalle"><table></table></x-tabla-responsive><x-tabla-responsive etiqueta="Listado" adaptable><table></table></x-tabla-responsive>', ['mensaje' => '<script>alert(1)</script>']);
        $dom = $this->dom($html);
        $this->assertSame(0, $dom->query('//script')->length);
        $this->assertSame(1, $dom->query('//*[contains(@class, "estado-vacio__mensaje") and contains(., "<script>")]')->length);
        $referencias = [];
        foreach ($dom->query('//*[@aria-describedby]') as $region) {
            $referencias[] = $region->getAttribute('aria-describedby');
        }
        $this->assertCount(2, array_unique($referencias));
        $this->assertSame(2, $dom->query('//p[contains(@class, "tabla-responsive__instruccion")]')->length);
    }

    public function test_el_css_contiene_el_ancho_de_tablas_y_adapta_las_listas_sin_ocultar_columnas(): void
    {
        $css = file_get_contents(public_path('css/administracion.css'));
        $this->assertMatchesRegularExpression('/\.tabla-responsive\s*\{[^}]*min-width:\s*0;[^}]*max-width:\s*100%;[^}]*overflow-x:\s*auto;/s', $css);
        $this->assertMatchesRegularExpression('/@media\s*\(max-width:\s*48rem\)\s*\{[^@]*\.tabla-adaptable\s*\{[^}]*min-width:\s*0;/s', $css);
        $this->assertMatchesRegularExpression('/\.tabla-adaptable\s+\[data-etiqueta\]::before\s*\{[^}]*content:\s*attr\(data-etiqueta\)/s', $css);
        $this->assertStringContainsString('.tabla-registros tbody th[scope="row"]', $css);
        $this->assertStringContainsString('.grupo-acciones-fila .boton', $css);
        $this->assertMatchesRegularExpression('/\.tabla-responsive__instruccion\s*\{\s*display:\s*block;/s', $css);
        $this->assertDoesNotMatchRegularExpression('/\.tabla-adaptable[^{}]*nth-child[^{}]*\{[^}]*display:\s*none/s', $css);
    }

    private function datos(): array
    {
        $usuario = Usuario::factory()->create(['rol' => 'administrador']);
        Usuario::factory()->create(['rol' => 'comercial']);
        $this->actingAs($usuario);
        $categoria = Categoria::create(['nombre' => 'Categoría accesible', 'activo' => true]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Cliente accesible',
            'rfc' => 'XAXX010101000',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Calle',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Equipo accesible',
            'codigo' => 'TAB-01',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 5,
            'requiere_numero_serie' => true,
            'activo' => true,
        ]);
        Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Servicio accesible',
            'codigo' => 'TAB-S',
            'unidad' => 'hora',
            'precio' => 100,
            'activo' => true,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-TABLAS',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'entrega_limite_en' => now()->addDays(5),
            'porcentaje_descuento' => 0,
            'total' => 100,
        ]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'producto_id' => $producto->id,
            'nombre_concepto' => 'Equipo accesible',
            'requiere_numero_serie' => true,
            'descripcion' => 'Descripción histórica',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);
        $cotizacion->enviosCorreo()->create([
            'usuario_id' => $usuario->id,
            'destinatario' => $cliente->correo,
            'tipo' => 'cotizacion',
            'resultado' => 'aceptada',
            'mensaje' => 'Aceptado',
            'intentado_en' => now(),
        ]);
        $venta = app(ServicioConversionVenta::class)->convertir($cotizacion, $usuario, 'efectivo', null, [$partida->id => ['TAB-SERIE']]);
        // Otra cotización aceptada mantiene visible la tabla de pendientes y el formulario de cierre.
        $pendiente = Cotizacion::create([
            'folio' => 'COT-TABLAS-PENDIENTE',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'entrega_limite_en' => now()->addDays(5),
            'porcentaje_descuento' => 0,
            'total' => 100,
        ]);
        $pendiente->partidas()->create([
            'tipo' => 'otro',
            'nombre_concepto' => 'Concepto libre',
            'descripcion' => 'Concepto libre',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);
        $pendiente->enviosCorreo()->create([
            'usuario_id' => $usuario->id,
            'destinatario' => $cliente->correo,
            'tipo' => 'cotizacion',
            'resultado' => 'aceptada',
            'mensaje' => 'Aceptado',
            'intentado_en' => now(),
        ]);

        return [$categoria, $pendiente, $venta];
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($documento);
    }
}
