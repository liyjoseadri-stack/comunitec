<?php

namespace Tests\Support;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

trait PreparaDominio
{
    public static function nombresHistoricosVacios(): iterable
    {
        yield 'espacio' => [' '];
        yield 'tabulacion' => ["\t"];
        yield 'retorno' => ["\r"];
        yield 'salto de linea' => ["\n"];
        yield 'tabulacion vertical' => ["\v"];
        yield 'salto de pagina' => ["\f"];
        yield 'combinados' => [" \t\r\n\v\f "];
    }

    private function migracionDominio(): Migration
    {
        return require database_path('migrations/2026_10_14_000002_agregar_restricciones_de_dominio.php');
    }

    private function prepararDominio(): array
    {
        $usuario = Usuario::factory()->create();
        $cliente = Cliente::create([
            'tipo' => 'moral', 'nombre' => 'Cliente de integridad', 'rfc' => 'INT010101AA1',
            'correo' => 'integridad@example.test', 'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba', 'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-DOMINIO', 'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id, 'estado' => 'venta', 'total' => 100,
        ]);
        $producto = Producto::create([
            'nombre' => 'Equipo', 'codigo' => 'DOM-PROD', 'unidad' => 'pieza',
            'precio' => 100, 'existencias' => 5,
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Instalación', 'codigo' => 'DOM-SERV', 'unidad' => 'hora', 'precio' => 100,
        ]);
        $venta = Venta::create([
            'folio' => 'VEN-DOMINIO', 'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id,
            'tipo_cliente' => $cliente->tipo, 'nombre_cliente' => $cliente->nombre,
            'rfc_cliente' => $cliente->rfc, 'correo_cliente' => $cliente->correo,
            'telefono_cliente' => $cliente->telefono, 'direccion_cliente' => $cliente->direccion,
            'codigo_postal_cliente' => $cliente->codigo_postal,
            'nombre_responsable' => $usuario->nombre, 'correo_responsable' => $usuario->correo,
            'vendida_en' => now(), 'metodo_pago' => 'efectivo',
            'subtotal' => 100, 'porcentaje_descuento' => 0, 'total' => 100,
        ]);
        $partida = [
            'tipo' => 'producto', 'producto_id' => $producto->id, 'servicio_id' => null,
            'descripcion' => 'Equipo acordado', 'nombre_concepto' => 'Equipo histórico',
            'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100,
        ];
        $cotizada = DB::table('partidas_cotizacion')->insertGetId([
            ...$partida, 'cotizacion_id' => $cotizacion->id,
        ]);

        return [
            'cotizaciones' => $cotizacion->getAttributes(),
            'partidas_cotizacion' => [...$partida, 'cotizacion_id' => $cotizacion->id],
            'partidas_venta' => [...$partida, 'venta_id' => $venta->id, 'partida_cotizacion_id' => $cotizada],
            'piezas_inventario' => [
                'producto_id' => $producto->id, 'numero_serie' => 'DOM-SERIE',
                'estado' => 'disponible', 'cotizacion_id' => null, 'partida_venta_id' => null,
            ],
            'producto_id' => $producto->id,
            'servicio_id' => $servicio->id,
        ];
    }

    private function filasDominio(): array
    {
        $filas = [];
        foreach (['cotizaciones', 'partidas_cotizacion', 'partidas_venta', 'piezas_inventario'] as $tabla) {
            $filas[$tabla] = DB::table($tabla)->orderBy('id')->get()->toArray();
        }

        return $filas;
    }
}
