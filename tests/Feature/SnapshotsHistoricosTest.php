<?php

namespace Tests\Feature;

use App\Mail\CorreoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PartidaVenta;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\ServicioConversionVenta;
use App\Services\ServicioCreacionCotizacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SnapshotsHistoricosTest extends TestCase
{
    use DatabaseMigrations;

    public function test_la_migracion_rellena_conceptos_existentes_sin_cambiar_importes_ni_relaciones(): void
    {
        $restricciones = require database_path('migrations/2026_10_14_000002_agregar_restricciones_de_dominio.php');
        $restricciones->down();
        $migracion = $this->migracionSnapshots();
        $migracion->down();
        [$cotizacion, $venta, $producto, $servicio] = $this->prepararDocumentos();
        $casos = [
            [
                'tipo' => 'producto', 'producto_id' => $producto->id, 'servicio_id' => null,
                'descripcion' => 'Equipo tal como fue cotizado', 'nombre_concepto' => 'Equipo de catálogo',
                'codigo_concepto' => 'EQ-001', 'marca_concepto' => 'Marca original',
                'modelo_concepto' => 'Modelo original', 'unidad_concepto' => 'pieza',
                'requiere_numero_serie' => true,
            ],
            [
                'tipo' => 'servicio', 'producto_id' => null, 'servicio_id' => $servicio->id,
                'descripcion' => 'Servicio con alcance acordado', 'nombre_concepto' => 'Instalación original',
                'codigo_concepto' => 'SER-001', 'marca_concepto' => null,
                'modelo_concepto' => null, 'unidad_concepto' => 'hora',
                'requiere_numero_serie' => false,
            ],
            [
                'tipo' => 'otro', 'producto_id' => null, 'servicio_id' => null,
                'descripcion' => 'Concepto libre acordado', 'nombre_concepto' => 'Concepto libre acordado',
                'codigo_concepto' => null, 'marca_concepto' => null,
                'modelo_concepto' => null, 'unidad_concepto' => null,
                'requiere_numero_serie' => false,
            ],
            [
                'tipo' => 'producto', 'producto_id' => null, 'servicio_id' => null,
                'descripcion' => '', 'nombre_concepto' => 'Concepto histórico',
                'codigo_concepto' => null, 'marca_concepto' => null,
                'modelo_concepto' => null, 'unidad_concepto' => null,
                'requiere_numero_serie' => false,
            ],
            [
                'tipo' => 'servicio', 'producto_id' => null, 'servicio_id' => null,
                'descripcion' => 'Servicio retirado', 'nombre_concepto' => 'Concepto histórico',
                'codigo_concepto' => null, 'marca_concepto' => null,
                'modelo_concepto' => null, 'unidad_concepto' => null,
                'requiere_numero_serie' => false,
            ],
        ];
        $identificadores = [];

        foreach ($casos as $caso) {
            $datos = [
                'producto_id' => $caso['producto_id'],
                'servicio_id' => $caso['servicio_id'],
                'tipo' => $caso['tipo'],
                'descripcion' => $caso['descripcion'],
                'cantidad' => 2,
                'precio_unitario' => 125,
                'subtotal' => 232.5,
            ];
            $cotizada = DB::table('partidas_cotizacion')->insertGetId([
                ...$datos, 'cotizacion_id' => $cotizacion->id, 'porcentaje_descuento' => 7,
            ]);
            $vendida = DB::table('partidas_venta')->insertGetId([
                ...$datos, 'venta_id' => $venta->id, 'partida_cotizacion_id' => $cotizada,
            ]);
            $identificadores[] = [$cotizada, $vendida];
        }

        $migracion->up();
        $restricciones->up();

        foreach ($casos as $indice => $caso) {
            foreach (['partidas_cotizacion', 'partidas_venta'] as $posicion => $tabla) {
                $this->assertDatabaseHas($tabla, [
                    'id' => $identificadores[$indice][$posicion],
                    ...$caso,
                    'cantidad' => 2,
                    'precio_unitario' => 125,
                    'porcentaje_descuento' => 7,
                    'subtotal' => 232.5,
                ]);
            }
        }
    }

    public function test_la_reversion_conserva_partidas_y_columnas_anteriores(): void
    {
        $restricciones = require database_path('migrations/2026_10_14_000002_agregar_restricciones_de_dominio.php');
        $restricciones->down();
        [$cotizacion, $venta, $producto] = $this->prepararDocumentos();
        [$cotizada, $vendida] = $this->crearPartidas($cotizacion, $venta, $producto);
        $anteriores = [];
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $anteriores[$tabla] = Schema::getColumnListing($tabla);
        }

        $migracion = $this->migracionSnapshots();
        $migracion->down();

        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $eliminadas = [
                'nombre_concepto', 'codigo_concepto', 'marca_concepto',
                'modelo_concepto', 'unidad_concepto', 'requiere_numero_serie',
            ];
            if ($tabla === 'partidas_venta') {
                $eliminadas[] = 'porcentaje_descuento';
            }
            $this->assertEqualsCanonicalizing(
                array_values(array_diff($anteriores[$tabla], $eliminadas)),
                Schema::getColumnListing($tabla)
            );
            $this->assertDatabaseCount($tabla, 1);
        }
        $this->assertDatabaseHas('partidas_cotizacion', [
            'id' => $cotizada->id, 'producto_id' => $producto->id,
            'descripcion' => 'Descripción acordada', 'porcentaje_descuento' => 5, 'subtotal' => 95,
        ]);
        $this->assertDatabaseHas('partidas_venta', [
            'id' => $vendida->id, 'partida_cotizacion_id' => $cotizada->id, 'subtotal' => 95,
        ]);

        $migracion->up();
        $restricciones->up();
    }

    public function test_los_modelos_guardan_snapshots_y_los_priorizan_tras_editar_o_eliminar_catalogo(): void
    {
        [$cotizacion, $venta, $producto] = $this->prepararDocumentos();
        $partidas = $this->crearPartidas($cotizacion, $venta, $producto, [
            'nombre_concepto' => 'Nombre histórico', 'codigo_concepto' => 'HIST-001',
            'marca_concepto' => 'Marca histórica', 'modelo_concepto' => 'Modelo histórico',
            'unidad_concepto' => 'caja', 'requiere_numero_serie' => true,
        ]);
        $producto->update(['nombre' => 'Nombre nuevo', 'codigo' => 'COD-NUEVO']);

        foreach ($partidas as $partida) {
            $partida->refresh();
            $this->assertSame('Nombre histórico', $partida->nombre_concepto);
            $this->assertSame('Nombre histórico', $partida->nombreHistorico());
            $this->assertSame('HIST-001', $partida->codigoHistorico());
            $this->assertSame('Marca histórica', $partida->marca_concepto);
            $this->assertSame('Modelo histórico', $partida->modelo_concepto);
            $this->assertSame('caja', $partida->unidad_concepto);
            $this->assertTrue($partida->requiere_numero_serie);
            $this->assertSame('5.00', $partida->porcentaje_descuento);
        }

        $producto->delete();

        foreach ($partidas as $partida) {
            $partida->refresh();
            $this->assertNull($partida->producto_id);
            $this->assertSame('Nombre histórico', $partida->nombreHistorico());
            $this->assertSame('HIST-001', $partida->codigoHistorico());
        }
    }

    #[DataProvider('modelosDePartida')]
    public function test_los_accesores_resuelven_heredados_y_respetan_un_codigo_historico_nulo(string $modelo): void
    {
        $producto = new Producto(['nombre' => 'Equipo heredado', 'codigo' => 'HER-001']);
        $partida = new $modelo(['descripcion' => 'Descripción heredada']);
        $partida->setRelation('producto', $producto);
        $partida->setRelation('servicio', null);

        $this->assertSame('Equipo heredado', $partida->nombreHistorico());
        $this->assertSame('HER-001', $partida->codigoHistorico());

        $partida->nombre_concepto = 'Nombre guardado sin código';
        $this->assertSame('Nombre guardado sin código', $partida->nombreHistorico());
        $this->assertNull($partida->codigoHistorico());

        $partida->nombre_concepto = '   ';
        $partida->setRelation('producto', null);
        $partida->setRelation('servicio', new Servicio(['nombre' => 'Servicio heredado', 'codigo' => 'SER-HER']));
        $this->assertSame('Servicio heredado', $partida->nombreHistorico());
        $this->assertSame('SER-HER', $partida->codigoHistorico());

        $partida->setRelation('servicio', null);
        $this->assertSame('Descripción heredada', $partida->nombreHistorico());
        $this->assertNull($partida->codigoHistorico());

        $partida->descripcion = '   ';
        $this->assertSame('Concepto histórico', $partida->nombreHistorico());
    }

    public static function modelosDePartida(): array
    {
        return [[PartidaCotizacion::class], [PartidaVenta::class]];
    }

    public function test_crear_por_api_guarda_datos_comerciales_independientes_para_productos_y_servicios(): void
    {
        [$anterior, , $producto, $servicio] = $this->prepararDocumentos();
        $usuario = $anterior->responsable;
        $usuario->update(['rol' => Usuario::ROL_COMERCIAL]);
        $this->actingAs($usuario)->postJson('/api/cotizaciones', [
            'cliente_id' => $anterior->cliente_id,
            'items' => [
                ['tipo' => 'producto', 'producto_id' => $producto->id, 'precio_unitario' => 1, 'descripcion' => 'Primera configuración', 'cantidad' => 1, 'porcentaje_descuento' => 5],
                ['tipo' => 'producto', 'producto_id' => $producto->id, 'precio_unitario' => 1, 'descripcion' => 'Segunda configuración', 'cantidad' => 2, 'porcentaje_descuento' => 10],
                ['tipo' => 'servicio', 'servicio_id' => $servicio->id, 'precio_unitario' => 1, 'descripcion' => 'Alcance acordado', 'cantidad' => 1.5, 'porcentaje_descuento' => 0],
            ],
        ])->assertCreated();

        $partidas = Cotizacion::whereKeyNot($anterior->id)->sole()->partidas;
        $this->assertSame(['Equipo de catálogo', 'Equipo de catálogo', 'Instalación original'], $partidas->pluck('nombre_concepto')->all());
        $this->assertSame(['EQ-001', 'EQ-001', 'SER-001'], $partidas->pluck('codigo_concepto')->all());
        $this->assertSame(['949.05', '1798.20', '675.00'], $partidas->pluck('subtotal')->all());
        $this->assertTrue($partidas[0]->requiere_numero_serie);
        $this->assertSame('Marca original', $partidas[0]->marca_concepto);
        $this->assertSame('Modelo original', $partidas[0]->modelo_concepto);
        $this->assertSame('hora', $partidas[2]->unidad_concepto);
        $this->assertFalse($partidas[2]->requiere_numero_serie);
    }

    public function test_editar_conserva_snapshot_y_precio_y_cambiar_articulo_los_reemplaza(): void
    {
        [$cotizacion, , $producto, $servicio] = $this->prepararDocumentos();
        $cotizacion->venta()->delete();
        $cotizacion->update(['estado' => 'borrador']);
        $usuario = $cotizacion->responsable;
        $usuario->update(['rol' => Usuario::ROL_COMERCIAL]);
        $datos = ['tipo' => 'producto', 'producto_id' => $producto->id, 'descripcion' => 'Acuerdo inicial', 'cantidad' => 1, 'porcentaje_descuento' => 5];
        $this->actingAs($usuario)->post(route('cotizaciones.partidas.guardar', $cotizacion), $datos)->assertSessionHasNoErrors();
        $partida = $cotizacion->partidas()->sole();
        $this->assertSame('Equipo de catálogo', $partida->nombre_concepto);
        $producto->update(['nombre' => 'Nombre cambiado', 'codigo' => 'NUEVO', 'precio' => 5000, 'requiere_numero_serie' => false]);
        $this->put(route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]), [...$datos, 'cantidad' => 2])->assertSessionHasNoErrors();
        $partida->refresh();
        $this->assertSame('Equipo de catálogo', $partida->nombre_concepto);
        $this->assertSame('EQ-001', $partida->codigo_concepto);
        $this->assertTrue($partida->requiere_numero_serie);
        $this->assertSame('999.00', $partida->precio_unitario);

        $this->put(route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]), [
            'tipo' => 'servicio', 'servicio_id' => $servicio->id, 'descripcion' => 'Nuevo acuerdo', 'cantidad' => 2,
        ])->assertSessionHasNoErrors();
        $partida->refresh();
        $this->assertSame('Instalación original', $partida->nombre_concepto);
        $this->assertSame('SER-001', $partida->codigo_concepto);
        $this->assertNull($partida->marca_concepto);
        $this->assertFalse($partida->requiere_numero_serie);
        $this->assertSame('450.00', $partida->precio_unitario);

        $this->put(route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]), [
            'tipo' => 'otro', 'descripcion' => 'Concepto libre nuevo', 'cantidad' => 1, 'precio_unitario' => 50,
        ])->assertSessionHasNoErrors();
        $partida->refresh();
        $this->assertSame('Concepto libre nuevo', $partida->nombre_concepto);
        $this->assertNull($partida->codigo_concepto);
        $this->assertNull($partida->unidad_concepto);
        $this->assertNull($partida->servicio_id);
        $this->put(route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]), [
            'tipo' => 'otro', 'descripcion' => 'Concepto libre revisado', 'cantidad' => 1, 'precio_unitario' => 50,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Concepto libre revisado', $partida->fresh()->nombre_concepto);
    }

    public function test_documentos_y_conversion_conservan_snapshots_tras_editar_todo_el_catalogo(): void
    {
        [$anterior, , $producto, $servicio] = $this->prepararDocumentos();
        $usuario = $anterior->responsable;
        $usuario->update(['rol' => Usuario::ROL_COMERCIAL]);
        $cotizacion = app(ServicioCreacionCotizacion::class)->crear([
            'cliente_id' => $anterior->cliente_id,
            'items' => [
                ['tipo' => 'producto', 'producto_id' => $producto->id, 'descripcion' => 'Equipo acordado', 'cantidad' => 1, 'porcentaje_descuento' => 5],
                ['tipo' => 'servicio', 'servicio_id' => $servicio->id, 'descripcion' => 'Instalación acordada', 'cantidad' => 1, 'porcentaje_descuento' => 10],
            ],
        ], $usuario);
        $cotizacion->update(['estado' => 'aceptada', 'aceptada_en' => now(), 'entrega_limite_en' => now()->addDays(5)]);
        $producto->update(['nombre' => 'Equipo cambiado', 'codigo' => 'EQ-NUEVO', 'marca' => 'Marca cambiada', 'modelo' => 'Modelo cambiado', 'unidad' => 'caja', 'precio' => 5000, 'requiere_numero_serie' => false]);
        $servicio->update(['nombre' => 'Servicio cambiado', 'codigo' => 'SER-NUEVO', 'unidad' => 'jornada', 'precio' => 9000]);
        $this->actingAs($usuario)->get(route('cotizaciones.detalle', $cotizacion))->assertOk()->assertSee('Equipo de catálogo')->assertSee('EQ-001')->assertSee('Instalación original');
        $formulario = $this->get(route('ventas.crear', $cotizacion))->assertOk()->assertSee('Equipo de catálogo')->assertSee('Instalación original')->assertDontSee('Equipo cambiado');
        $partida = $cotizacion->partidas()->where('tipo', 'producto')->sole();
        $formulario->assertSee('name="series['.$partida->id.'][]"', false);
        $pdf = view('cotizaciones.pdf', ['cotizacion' => $cotizacion->fresh()->load('cliente', 'responsable', 'partidas')])->render();
        foreach (['Equipo de catálogo', 'EQ-001', 'Marca original', 'Modelo original', 'hora', 'SER-001'] as $dato) {
            $this->assertStringContainsString($dato, $pdf);
        }
        $this->assertStringNotContainsString('cambiado', $pdf);
        $adjunto = (new CorreoCotizacion($cotizacion->fresh()))->attachments()[0];
        $this->assertSame('application/pdf', $adjunto->mime);
        $this->assertSame($cotizacion->folio.'.pdf', $adjunto->as);
        $datosPdf = $adjunto->attachWith(fn () => null, fn ($datos) => $datos());
        $this->assertStringStartsWith('%PDF-', $datosPdf);

        $venta = app(ServicioConversionVenta::class)->convertir($cotizacion, $usuario, 'efectivo', null, [$partida->id => ['HIST-SERIE']]);
        foreach ($cotizacion->partidas()->get() as $cotizada) {
            $vendida = $venta->partidas()->where('partida_cotizacion_id', $cotizada->id)->sole();
            foreach (['nombre_concepto', 'codigo_concepto', 'marca_concepto', 'modelo_concepto', 'unidad_concepto', 'requiere_numero_serie', 'descripcion', 'cantidad', 'precio_unitario', 'porcentaje_descuento', 'subtotal'] as $campo) {
                $this->assertSame($cotizada->$campo, $vendida->$campo, $campo);
            }
        }
        // Las piezas entregadas protegen la eliminación física del producto;
        // simulamos la pérdida de la relación de catálogo en ambas partidas.
        $cotizacion->partidas()->where('tipo', 'producto')->update(['producto_id' => null]);
        $venta->partidas()->where('tipo', 'producto')->update(['producto_id' => null]);
        $servicio->delete();
        $this->get(route('cotizaciones.detalle', $cotizacion))->assertOk()->assertSee('Equipo de catálogo')->assertSee('Instalación original');
        $pdfCotizacion = view('cotizaciones.pdf', ['cotizacion' => $cotizacion->fresh()->load('cliente', 'responsable', 'partidas')])->render();
        $this->assertStringContainsString('SER-001', $pdfCotizacion);
        $this->assertStringContainsString('EQ-001', $pdfCotizacion);
        $this->get(route('ventas.detalle', $venta))->assertOk()->assertSee('Equipo de catálogo')->assertSee('Instalación original');
        $pdf = view('ventas.pdf', ['venta' => $venta->fresh()->load('partidas.piezas')])->render();
        foreach (['Equipo de catálogo', 'EQ-001', 'Instalación original', 'SER-001', 'HIST-SERIE'] as $dato) {
            $this->assertStringContainsString($dato, $pdf);
        }
    }

    public function test_editar_partida_con_relacion_eliminada_preserva_su_historial(): void
    {
        [$cotizacion, , $producto] = $this->prepararDocumentos();
        $cotizacion->venta()->delete();
        $cotizacion->update(['estado' => 'borrador']);
        $usuario = $cotizacion->responsable;
        $usuario->update(['rol' => Usuario::ROL_COMERCIAL]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'producto', 'producto_id' => $producto->id, 'nombre_concepto' => 'Equipo histórico eliminado', 'codigo_concepto' => 'ELIM-001',
            'requiere_numero_serie' => false, 'descripcion' => 'Original', 'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100,
        ]);
        $producto->delete();
        $this->actingAs($usuario)->put(route('cotizaciones.partidas.actualizar', [$cotizacion, $partida]), [
            'tipo' => 'producto', 'producto_id' => null, 'descripcion' => 'Descripción revisada', 'cantidad' => 2,
        ])->assertSessionHasNoErrors();
        $partida->refresh();
        $this->assertSame('Equipo histórico eliminado', $partida->nombre_concepto);
        $this->assertSame('ELIM-001', $partida->codigo_concepto);
        $this->assertSame('200.00', $partida->subtotal);
        $this->get(route('cotizaciones.detalle', $cotizacion))->assertOk()->assertSee('Equipo histórico eliminado')->assertSee('ELIM-001');
    }

    private function migracionSnapshots(): Migration
    {
        $this->assertTrue(
            Schema::hasColumn('partidas_cotizacion', 'nombre_concepto'),
            'La migración debe agregar los snapshots históricos a las partidas.'
        );

        return require database_path('migrations/2026_10_14_000001_agregar_snapshots_a_partidas.php');
    }

    private function prepararDocumentos(): array
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral', 'nombre' => 'Cliente histórico', 'rfc' => 'CHI010101AA1',
            'correo' => 'cliente@example.test', 'telefono' => '9610000000',
            'direccion' => 'Dirección histórica', 'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-SNAP', 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id,
            'estado' => 'venta', 'total' => 95,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-SNAP', 'cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id, 'tipo_cliente' => $cliente->tipo,
            'nombre_cliente' => $cliente->nombre, 'rfc_cliente' => $cliente->rfc,
            'correo_cliente' => $cliente->correo, 'telefono_cliente' => $cliente->telefono,
            'direccion_cliente' => $cliente->direccion, 'codigo_postal_cliente' => $cliente->codigo_postal,
            'nombre_responsable' => $usuario->nombre, 'correo_responsable' => $usuario->correo,
            'vendida_en' => now(), 'metodo_pago' => 'efectivo', 'subtotal' => 95,
            'porcentaje_descuento' => 0, 'total' => 95,
        ]);
        $producto = Producto::create([
            'nombre' => 'Equipo de catálogo', 'codigo' => 'EQ-001', 'marca' => 'Marca original',
            'modelo' => 'Modelo original', 'unidad' => 'pieza', 'requiere_numero_serie' => true,
            'precio' => 999, 'existencias' => 5,
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Instalación original', 'codigo' => 'SER-001', 'unidad' => 'hora', 'precio' => 450,
        ]);

        return [$cotizacion, $venta, $producto, $servicio];
    }

    private function crearPartidas(Cotizacion $cotizacion, Venta $venta, Producto $producto, array $snapshot = []): array
    {
        $datos = [
            'producto_id' => $producto->id, 'tipo' => 'producto', 'descripcion' => 'Descripción acordada',
            'cantidad' => 1, 'precio_unitario' => 100, 'porcentaje_descuento' => 5, 'subtotal' => 95,
            ...$snapshot,
        ];
        $cotizada = $cotizacion->partidas()->create($datos);
        $vendida = $venta->partidas()->create([...$datos, 'partida_cotizacion_id' => $cotizada->id]);

        return [$cotizada, $vendida];
    }
}
