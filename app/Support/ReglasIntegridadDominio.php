<?php

namespace App\Support;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PiezaInventario;
use RuntimeException;

class ReglasIntegridadDominio
{
    public static function validarMotor(string $motor, ?string $version = null): void
    {
        if ($motor === 'sqlite') {
            return;
        }

        if ($motor !== 'mysql' || preg_match('/^(\d+\.\d+\.\d+)/', $version ?? '', $coincidencias) !== 1
            || str_contains(strtolower($version ?? ''), 'mariadb')
            || version_compare($coincidencias[1], '8.0.16', '<')) {
            throw new RuntimeException('Las restricciones de dominio requieren MySQL 8.0.16+ o SQLite. No se modificó el esquema.');
        }
    }

    public static function reglas(string $motor, string $prefijo = ''): array
    {
        // La comparación binaria evita aceptar mayúsculas o espacios finales
        // bajo la colación habitual de MySQL.
        $texto = fn (string $columna): string => $motor === 'mysql'
            ? 'BINARY '.$prefijo.$columna
            : $prefijo.$columna.' COLLATE BINARY';
        $lista = fn (array $valores): string => "'".implode("', '", $valores)."'";
        $estado = $texto('estado');
        $tipo = $texto('tipo');
        $producto = $prefijo.'producto_id';
        $servicio = $prefijo.'servicio_id';
        $nombre = $prefijo.'nombre_concepto';
        $cotizacion = $prefijo.'cotizacion_id';
        $venta = $prefijo.'partida_venta_id';
        $snapshot = self::snapshotValido($nombre);
        $tipoProducto = PartidaCotizacion::TIPO_PRODUCTO;
        $tipoServicio = PartidaCotizacion::TIPO_SERVICIO;
        $tipoOtro = PartidaCotizacion::TIPO_OTRO;
        $disponible = PiezaInventario::ESTADO_DISPONIBLE;
        $reservada = PiezaInventario::ESTADO_RESERVADA;
        $entregada = PiezaInventario::ESTADO_ENTREGADA;
        $relaciones = "(($tipo = '$tipoProducto' AND $servicio IS NULL AND ($producto IS NOT NULL OR $snapshot))"
            ." OR ($tipo = '$tipoServicio' AND $producto IS NULL AND ($servicio IS NOT NULL OR $snapshot))"
            ." OR ($tipo = '$tipoOtro' AND $producto IS NULL AND $servicio IS NULL))";
        $reglas = [
            'dominio_cotizaciones_estado' => [
                'tabla' => 'cotizaciones', 'check' => true,
                'expresion' => "$estado IN (".$lista(Cotizacion::estados()).')',
            ],
            'dominio_piezas_estado' => [
                'tabla' => 'piezas_inventario', 'check' => true,
                'expresion' => "$estado IN (".$lista(PiezaInventario::estados()).')',
            ],
            'dominio_piezas_relaciones' => [
                'tabla' => 'piezas_inventario', 'check' => false,
                'expresion' => "(($estado = '$disponible' AND $cotizacion IS NULL AND $venta IS NULL)"
                    ." OR ($estado = '$reservada' AND $cotizacion IS NOT NULL AND $venta IS NULL)"
                    ." OR ($estado = '$entregada' AND $cotizacion IS NULL AND $venta IS NOT NULL))",
            ],
        ];
        foreach (['partidas_cotizacion', 'partidas_venta'] as $tabla) {
            $reglas['dominio_'.$tabla.'_tipo'] = [
                'tabla' => $tabla, 'check' => true,
                'expresion' => "$tipo IN (".$lista(PartidaCotizacion::tipos()).')',
            ];
            $reglas['dominio_'.$tabla.'_relaciones'] = [
                'tabla' => $tabla, 'check' => false, 'expresion' => $relaciones,
            ];
        }

        return $reglas;
    }

    public static function protecciones(string $motor): array
    {
        $protecciones = [];
        foreach (self::reglas($motor) as $nombre => $regla) {
            $tabla = $regla['tabla'];
            if ($motor === 'mysql' && $regla['check']) {
                $expresion = $regla['expresion'];
                $protecciones[$nombre] = [
                    'crear' => "ALTER TABLE `$tabla` ADD CONSTRAINT `$nombre` CHECK (COALESCE(($expresion), 0) = 1)",
                    'eliminar' => "ALTER TABLE `$tabla` DROP CHECK `$nombre`",
                    'check' => true,
                ];

                continue;
            }
            $expresion = self::reglas($motor, 'NEW.')[$nombre]['expresion'];
            foreach (['insert' => 'INSERT', 'update' => 'UPDATE'] as $sufijo => $evento) {
                $identificador = $nombre.'_'.$sufijo;
                $condicion = "NOT COALESCE(($expresion), 0)";
                $protecciones[$identificador] = self::trigger($motor, $identificador, $tabla, $evento, $condicion);
            }
        }

        // MySQL no dispara triggers de la tabla hija por acciones de una FK.
        // Estas guardas impiden que SET NULL deje una reserva sin cotización
        // o una partida sin relación y sin identidad histórica.
        $guardas = [
            'dominio_cotizaciones_reservadas_delete' => [
                'cotizaciones',
                "EXISTS (SELECT 1 FROM piezas_inventario WHERE cotizacion_id = OLD.id AND estado = '".PiezaInventario::ESTADO_RESERVADA."')",
            ],
        ];
        foreach (['productos' => 'producto_id', 'servicios' => 'servicio_id'] as $tabla => $columna) {
            $condiciones = [];
            foreach (['partidas_cotizacion', 'partidas_venta'] as $partidas) {
                $snapshot = self::snapshotValido('nombre_concepto');
                $condiciones[] = "EXISTS (SELECT 1 FROM $partidas WHERE $columna = OLD.id AND NOT ($snapshot))";
            }
            $guardas['dominio_'.$tabla.'_historial_delete'] = [$tabla, implode(' OR ', $condiciones)];
        }
        foreach ($guardas as $nombre => [$tabla, $condicion]) {
            $protecciones[$nombre] = self::trigger($motor, $nombre, $tabla, 'DELETE', $condicion);
        }

        return $protecciones;
    }

    private static function snapshotValido(string $columna): string
    {
        // REPLACE y CHAR tienen la misma semántica para estos caracteres ASCII
        // en MySQL/SQLite. Solo se comprueba si queda texto; no se altera el dato.
        foreach ([9, 10, 11, 12, 13, 32] as $codigo) {
            $columna = "REPLACE($columna, CHAR($codigo), '')";
        }

        return "COALESCE(LENGTH($columna), 0) > 0";
    }

    private static function trigger(string $motor, string $nombre, string $tabla, string $evento, string $condicion): array
    {
        $crear = $motor === 'mysql'
            ? "CREATE TRIGGER `$nombre` BEFORE $evento ON `$tabla` FOR EACH ROW BEGIN IF $condicion THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$nombre'; END IF; END"
            : "CREATE TRIGGER `$nombre` BEFORE $evento ON `$tabla` FOR EACH ROW WHEN $condicion BEGIN SELECT RAISE(ABORT, '$nombre'); END";

        return [
            'crear' => $crear,
            'eliminar' => "DROP TRIGGER IF EXISTS `$nombre`",
            'check' => false,
        ];
    }
}
