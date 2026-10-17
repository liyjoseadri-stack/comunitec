<?php

namespace Tests\Feature;

use App\Support\ReglasIntegridadDominio;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\PreparaDominio;
use Tests\TestCase;

class RestriccionesDominioTest extends TestCase
{
    use DatabaseMigrations;
    use PreparaDominio;

    public static function combinacionesInvalidas(): iterable
    {
        $casos = ['cotizaciones' => [
            ['estado' => 'desconocido'], ['estado' => 'BORRADOR'], ['estado' => 'borrador '],
        ]];
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $casos[$tabla] = [
                ['tipo' => 'desconocido'], ['tipo' => 'PRODUCTO'], ['tipo' => 'producto '],
                ['servicio_id' => 1], ['tipo' => 'servicio'],
                ['tipo' => 'otro'], ['tipo' => 'otro', 'producto_id' => null, 'servicio_id' => 1],
                ['producto_id' => null, 'nombre_concepto' => null],
                ['producto_id' => null, 'nombre_concepto' => '   '],
                ['tipo' => 'servicio', 'producto_id' => null, 'nombre_concepto' => ''],
            ];
            foreach (self::nombresHistoricosVacios() as [$nombre]) {
                foreach (['producto', 'servicio'] as $tipo) {
                    $casos[$tabla][] = [
                        'tipo' => $tipo, 'producto_id' => null, 'servicio_id' => null,
                        'nombre_concepto' => $nombre,
                    ];
                }
            }
        }
        $casos['piezas_inventario'] = [
            ['estado' => 'desconocido'], ['estado' => 'DISPONIBLE'], ['estado' => 'disponible '],
            ['cotizacion_id' => 1], ['partida_venta_id' => 1],
            ['estado' => 'reservada'],
            ['estado' => 'reservada', 'cotizacion_id' => 1, 'partida_venta_id' => 1],
            ['estado' => 'entregada'],
            ['estado' => 'entregada', 'cotizacion_id' => 1, 'partida_venta_id' => 1],
        ];
        foreach ($casos as $tabla => $combinaciones) {
            foreach ($combinaciones as $indice => $cambios) {
                foreach (['insert', 'update'] as $operacion) {
                    yield "$tabla $indice $operacion" => [$tabla, $cambios, $operacion];
                }
            }
        }
    }

    #[DataProvider('combinacionesInvalidas')]
    public function test_sql_directo_rechaza_combinaciones_invalidas(string $tabla, array $cambios, string $operacion): void
    {
        $datos = $this->prepararDominio();
        DB::table('partidas_venta')->insert($datos['partidas_venta']);
        $valida = $datos[$tabla];
        unset($valida['id']);
        if ($tabla === 'cotizaciones') {
            $valida['folio'] = 'COT-INSERT';
        }
        if ($operacion === 'update') {
            $id = $tabla === 'piezas_inventario'
                ? DB::table($tabla)->insertGetId($valida)
                : DB::table($tabla)->value('id');
        } elseif ($tabla === 'partidas_venta') {
            DB::table('partidas_venta')->delete();
        }

        try {
            $operacion === 'insert'
                ? DB::table($tabla)->insert([...$valida, ...$cambios])
                : DB::table($tabla)->where('id', $id)->update($cambios);
        } catch (QueryException $error) {
            $this->assertStringContainsString('dominio_', $error->getMessage());

            return;
        }
        $this->fail('La base aceptó una combinación incompatible por '.$operacion.'.');
    }

    public function test_la_migracion_aborta_antes_de_crear_protecciones_y_conserva_datos_invalidos(): void
    {
        $this->prepararDominio();
        $migracion = $this->migracionDominio();
        $migracion->down();
        DB::table('cotizaciones')->update(['estado' => 'anterior']);
        $antes = $this->filasDominio();

        try {
            $migracion->up();
            $this->fail('La migración debía detenerse ante datos incompatibles.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('dominio:auditar', $error->getMessage());
        }
        $this->assertEquals($antes, $this->filasDominio());
        $this->assertSame([], DB::select("SELECT name FROM sqlite_master WHERE type = 'trigger' AND name LIKE 'dominio_%'"));
    }

    public function test_down_elimina_solo_sus_protecciones_y_up_las_restaura_sin_alterar_datos(): void
    {
        $this->prepararDominio();
        DB::unprepared('CREATE TRIGGER ajeno AFTER UPDATE ON cotizaciones BEGIN SELECT 1; END');
        $antes = $this->filasDominio();
        $esquema = DB::select("SELECT name, sql FROM sqlite_master WHERE type IN ('table', 'index') ORDER BY name");
        $migracion = $this->migracionDominio();
        $migracion->down();
        $this->assertCount(1, DB::select("SELECT name FROM sqlite_master WHERE type = 'trigger'"));
        DB::table('cotizaciones')->update(['estado' => 'anterior']);
        DB::table('cotizaciones')->update(['estado' => 'venta']);
        $migracion->up();

        $this->assertEquals($antes, $this->filasDominio());
        $this->assertEquals($esquema, DB::select("SELECT name, sql FROM sqlite_master WHERE type IN ('table', 'index') ORDER BY name"));
        $this->expectException(QueryException::class);
        DB::table('cotizaciones')->update(['estado' => 'anterior']);
    }

    public function test_borrar_catalogo_conserva_partidas_historicas_y_auditoria_limpia(): void
    {
        $datos = $this->prepararDominio();
        DB::table('partidas_venta')->insert($datos['partidas_venta']);
        DB::table('productos')->where('id', $datos['producto_id'])->delete();
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $this->assertDatabaseHas($tabla, ['producto_id' => null, 'nombre_concepto' => 'Equipo histórico']);
            DB::table($tabla)->update(['descripcion' => 'Descripción revisada']);
        }
        $this->artisan('dominio:auditar')->assertExitCode(0);
    }

    public function test_una_falla_ddl_limpia_solo_las_protecciones_creadas_por_esa_ejecucion(): void
    {
        $this->prepararDominio();
        $migracion = $this->migracionDominio();
        $migracion->down();
        $nombreAjeno = 'dominio_partidas_cotizacion_relaciones_insert';
        DB::unprepared('CREATE TRIGGER '.$nombreAjeno.' AFTER UPDATE ON cotizaciones BEGIN SELECT 1; END');
        $antes = $this->filasDominio();

        try {
            $migracion->up();
            $this->fail('La colisión de nombres debía detener el DDL.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('already exists', $error->getMessage());
        }
        $this->assertEquals($antes, $this->filasDominio());
        $this->assertSame([$nombreAjeno], array_column(DB::select("SELECT name FROM sqlite_master WHERE type = 'trigger'"), 'name'));
    }

    public function test_los_estados_y_relaciones_validos_admiten_insercion_y_actualizacion(): void
    {
        $datos = $this->prepararDominio();
        $vendida = DB::table('partidas_venta')->insertGetId($datos['partidas_venta']);
        foreach (['borrador', 'pendiente', 'aceptada', 'rechazada', 'cancelada', 'vencida', 'venta'] as $estado) {
            DB::table('cotizaciones')->update(['estado' => $estado]);
            $this->assertSame($estado, DB::table('cotizaciones')->value('estado'));
        }
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            foreach ([
                ['tipo' => 'producto', 'producto_id' => $datos['producto_id'], 'servicio_id' => null],
                ['tipo' => 'servicio', 'producto_id' => null, 'servicio_id' => $datos['servicio_id']],
                ['tipo' => 'otro', 'producto_id' => null, 'servicio_id' => null],
                ['tipo' => 'producto', 'producto_id' => null, 'servicio_id' => null],
                ['tipo' => 'servicio', 'producto_id' => null, 'servicio_id' => null],
            ] as $combinacion) {
                DB::table($tabla)->update($combinacion);
                $this->assertDatabaseHas($tabla, $combinacion);
            }
        }
        $serie = DB::table('piezas_inventario')->insertGetId($datos['piezas_inventario']);
        foreach ([
            ['estado' => 'reservada', 'cotizacion_id' => 1, 'partida_venta_id' => null],
            ['estado' => 'entregada', 'cotizacion_id' => null, 'partida_venta_id' => $vendida],
            ['estado' => 'disponible', 'cotizacion_id' => null, 'partida_venta_id' => null],
        ] as $combinacion) {
            DB::table('piezas_inventario')->where('id', $serie)->update($combinacion);
            $this->assertDatabaseHas('piezas_inventario', ['id' => $serie, ...$combinacion]);
        }
        $this->artisan('dominio:auditar')->assertExitCode(0);
    }

    public function test_no_se_puede_borrar_cotizacion_con_piezas_reservadas(): void
    {
        $datos = $this->prepararDominio();
        DB::table('ventas')->delete();
        DB::table('piezas_inventario')->insert([
            ...$datos['piezas_inventario'], 'estado' => 'reservada', 'cotizacion_id' => 1,
        ]);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('dominio_cotizaciones_reservadas_delete');
        DB::table('cotizaciones')->delete();
    }

    #[DataProvider('catalogos')]
    public function test_borrar_catalogo_sin_snapshot_no_pierde_identidad_historica(string $tabla, string $columna, string $tipo): void
    {
        $datos = $this->prepararDominio();
        DB::table('partidas_cotizacion')->update([
            'tipo' => $tipo, 'producto_id' => null, 'servicio_id' => null,
            $columna => $datos[$columna], 'nombre_concepto' => null,
        ]);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('dominio_'.$tabla.'_historial_delete');
        DB::table($tabla)->delete();
    }

    public static function catalogos(): array
    {
        return [['productos', 'producto_id', 'producto'], ['servicios', 'servicio_id', 'servicio']];
    }

    public static function catalogosConSnapshotVacio(): iterable
    {
        foreach (self::catalogos() as [$catalogo, $columna, $tipo]) {
            foreach (['partidas_cotizacion', 'partidas_venta'] as $partidas) {
                foreach (self::nombresHistoricosVacios() as $caso => [$nombre]) {
                    yield "$catalogo $partidas $caso" => [$catalogo, $columna, $tipo, $partidas, $nombre];
                }
            }
        }
    }

    #[DataProvider('catalogosConSnapshotVacio')]
    public function test_borrar_catalogo_no_deja_partidas_con_nombre_compuesto_solo_por_espacios(
        string $catalogo,
        string $columna,
        string $tipo,
        string $partidas,
        string $nombre
    ): void {
        $datos = $this->prepararDominio();
        $snapshot = [
            'tipo' => $tipo, 'producto_id' => null, 'servicio_id' => null,
            $columna => $datos[$columna], 'nombre_concepto' => $nombre,
        ];
        if ($partidas === 'partidas_cotizacion') {
            DB::table($partidas)->update($snapshot);
        } else {
            DB::table($partidas)->insert([...$datos[$partidas], ...$snapshot]);
        }
        $antes = $this->filasDominio();

        try {
            DB::table($catalogo)->where('id', $datos[$columna])->delete();
            $this->fail('La eliminación dejaría una partida sin identidad histórica.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('dominio_'.$catalogo.'_historial_delete', $error->getMessage());
        }
        $this->assertEquals($antes, $this->filasDominio());
        $this->assertDatabaseHas($catalogo, ['id' => $datos[$columna]]);
    }

    #[DataProvider('versionesIncompatibles')]
    public function test_rechaza_motores_sin_garantia_de_enforcement(string $motor, ?string $version): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL 8.0.16+');
        ReglasIntegridadDominio::validarMotor($motor, $version);
    }

    public static function versionesIncompatibles(): array
    {
        return [
            ['mysql', '5.7.44'], ['mysql', '8.0.15'], ['mysql', '10.11.6-MariaDB'],
            ['mariadb', '11.4.2-MariaDB'], ['mysql', null], ['mysql', 'versión desconocida'],
            ['pgsql', '16.0'],
        ];
    }

    public function test_sql_mysql_genera_checks_sin_acciones_fk_y_triggers_con_reversion_exacta(): void
    {
        ReglasIntegridadDominio::validarMotor('mysql', '8.0.16');
        ReglasIntegridadDominio::validarMotor('mysql', '8.4.3-commercial');
        $protecciones = ReglasIntegridadDominio::protecciones('mysql');
        $checks = array_filter($protecciones, fn (array $proteccion): bool => $proteccion['check']);
        $this->assertCount(4, $checks);
        $this->assertCount(13, $protecciones);
        foreach ($protecciones as $nombre => $proteccion) {
            $this->assertLessThanOrEqual(64, strlen($nombre));
            $this->assertStringContainsString('`'.$nombre.'`', $proteccion['crear']);
            $this->assertStringContainsString('`'.$nombre.'`', $proteccion['eliminar']);
            if ($proteccion['check']) {
                $this->assertStringNotContainsString('_id', $proteccion['crear']);
                $this->assertStringContainsString('BINARY ', $proteccion['crear']);
                $this->assertMatchesRegularExpression('/CHECK \(COALESCE\(\(.+\), 0\) = 1\)$/', $proteccion['crear']);
                $this->assertStringContainsString('DROP CHECK', $proteccion['eliminar']);
            } else {
                $this->assertStringContainsString("SIGNAL SQLSTATE '45000'", $proteccion['crear']);
                $this->assertStringContainsString('BEFORE ', $proteccion['crear']);
                $this->assertStringContainsString('DROP TRIGGER', $proteccion['eliminar']);
            }
        }
        $relaciones = $protecciones['dominio_partidas_cotizacion_relaciones_update']['crear'];
        $this->assertStringContainsString('NEW.producto_id', $relaciones);
        $this->assertStringContainsString('NEW.servicio_id', $relaciones);
        $this->assertStringContainsString('NEW.nombre_concepto', $relaciones);
        $this->assertStringContainsString('OLD.id', $protecciones['dominio_cotizaciones_reservadas_delete']['crear']);
        foreach ([9, 10, 11, 12, 13, 32] as $codigo) {
            $this->assertStringContainsString('CHAR('.$codigo.')', $relaciones);
            foreach (['productos', 'servicios'] as $catalogo) {
                $this->assertStringContainsString('CHAR('.$codigo.')', $protecciones['dominio_'.$catalogo.'_historial_delete']['crear']);
            }
        }
    }

    public function test_el_nombre_historico_con_texto_real_se_conserva_aunque_tenga_espacios_ascii(): void
    {
        $datos = $this->prepararDominio();
        $nombre = " \t\r\n\v\f Equipo histórico \t\r\n\v\f ";
        DB::table('partidas_cotizacion')->update(['nombre_concepto' => $nombre]);
        DB::table('partidas_venta')->insert([...$datos['partidas_venta'], 'nombre_concepto' => $nombre]);
        DB::table('productos')->where('id', $datos['producto_id'])->delete();
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $this->assertDatabaseHas($tabla, ['producto_id' => null, 'nombre_concepto' => $nombre]);
            DB::table($tabla)->update(['descripcion' => 'Descripción revisada']);
        }
        $this->artisan('dominio:auditar')->assertExitCode(0);
    }
}
