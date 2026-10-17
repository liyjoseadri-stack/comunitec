<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['partidas_cotizacion', 'partidas_venta'] as $nombreTabla) {
            Schema::table($nombreTabla, function (Blueprint $tabla) use ($nombreTabla) {
                $tabla->string('nombre_concepto')->nullable();
                $tabla->string('codigo_concepto')->nullable();
                $tabla->string('marca_concepto')->nullable();
                $tabla->string('modelo_concepto')->nullable();
                $tabla->string('unidad_concepto')->nullable();
                $tabla->boolean('requiere_numero_serie')->nullable();

                if ($nombreTabla === 'partidas_venta') {
                    $tabla->decimal('porcentaje_descuento', 5, 2)->nullable();
                }
            });
        }

        foreach (['partidas_cotizacion', 'partidas_venta'] as $nombreTabla) {
            $this->rellenarSnapshots($nombreTabla);
        }
    }

    public function down(): void
    {
        foreach (['partidas_venta', 'partidas_cotizacion'] as $nombreTabla) {
            Schema::table($nombreTabla, function (Blueprint $tabla) use ($nombreTabla) {
                $tabla->dropColumn([
                    'nombre_concepto',
                    'codigo_concepto',
                    'marca_concepto',
                    'modelo_concepto',
                    'unidad_concepto',
                    'requiere_numero_serie',
                    ...($nombreTabla === 'partidas_venta' ? ['porcentaje_descuento'] : []),
                ]);
            });
        }
    }

    private function rellenarSnapshots(string $nombreTabla): void
    {
        DB::table($nombreTabla)->chunkById(500, function ($partidas) use ($nombreTabla) {
            $productos = DB::table('productos')
                ->whereIn('id', $partidas->pluck('producto_id')->filter())
                ->get()->keyBy('id');
            $servicios = DB::table('servicios')
                ->whereIn('id', $partidas->pluck('servicio_id')->filter())
                ->get()->keyBy('id');
            $descuentos = $nombreTabla === 'partidas_venta'
                ? DB::table('partidas_cotizacion')
                    ->whereIn('id', $partidas->pluck('partida_cotizacion_id'))
                    ->pluck('porcentaje_descuento', 'id')
                : collect();

            foreach ($partidas as $partida) {
                $articulo = match ($partida->tipo) {
                    'producto' => $productos->get($partida->producto_id),
                    'servicio' => $servicios->get($partida->servicio_id),
                    default => null,
                };
                $nombre = $partida->tipo === 'otro'
                    ? $partida->descripcion
                    : ($articulo->nombre ?? null);
                $snapshot = [
                    'nombre_concepto' => filled(trim($nombre ?? '')) ? $nombre : 'Concepto histórico',
                    'codigo_concepto' => $articulo->codigo ?? null,
                    'marca_concepto' => $articulo->marca ?? null,
                    'modelo_concepto' => $articulo->modelo ?? null,
                    'unidad_concepto' => $articulo->unidad ?? null,
                    'requiere_numero_serie' => (bool) ($articulo->requiere_numero_serie ?? false),
                ];

                if ($nombreTabla === 'partidas_venta') {
                    $snapshot['porcentaje_descuento'] = $descuentos->get($partida->partida_cotizacion_id, 0);
                }

                DB::table($nombreTabla)->where('id', $partida->id)->update($snapshot);
            }
        });
    }
};
