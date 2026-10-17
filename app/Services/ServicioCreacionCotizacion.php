<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Support\CalculosCotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicioCreacionCotizacion
{
    public function snapshot(Producto|Servicio $elemento): array
    {
        return [
            'nombre_concepto' => $elemento->nombre,
            'codigo_concepto' => $elemento->codigo,
            'marca_concepto' => $elemento instanceof Producto ? $elemento->marca : null,
            'modelo_concepto' => $elemento instanceof Producto ? $elemento->modelo : null,
            'unidad_concepto' => $elemento->unidad,
            'requiere_numero_serie' => $elemento instanceof Producto && $elemento->requiere_numero_serie,
        ];
    }

    public function crear(array $datos, Usuario $usuario): Cotizacion
    {
        return DB::transaction(function () use ($datos, $usuario): Cotizacion {
            $cotizacion = Cotizacion::create([
                'cliente_id' => $datos['cliente_id'],
                'usuario_id' => $usuario->id,
                'folio' => 'COT-'.now()->format('Ymd').'-'.Str::ulid(),
                'area_solicitante' => $datos['area_solicitante'] ?? null,
                'notas' => $datos['notas'] ?? null,
                'estado' => Cotizacion::ESTADO_BORRADOR,
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'total' => 0,
            ]);

            $totalConceptos = 0.0;
            foreach ($datos['items'] as $item) {
                $totalConceptos += $this->crearConcepto($cotizacion, $item);
            }

            $descuentoGeneral = (float) ($datos['porcentaje_descuento'] ?? 0);
            $cotizacion->update([
                'total' => CalculosCotizacion::descontar($totalConceptos, $descuentoGeneral),
            ]);

            return $cotizacion;
        });
    }

    private function crearConcepto(Cotizacion $cotizacion, array $item): float
    {
        $esProducto = $item['tipo'] === PartidaCotizacion::TIPO_PRODUCTO;
        $elemento = $esProducto
            ? Producto::where('activo', true)->findOrFail($item['producto_id'])
            : Servicio::where('activo', true)->findOrFail($item['servicio_id']);
        $precio = (float) $elemento->precio;
        $descuento = (float) $item['porcentaje_descuento'];
        $subtotal = CalculosCotizacion::descontar((float) $item['cantidad'] * $precio, $descuento);

        $cotizacion->partidas()->create([
            ...$this->snapshot($elemento),
            'tipo' => $item['tipo'],
            'producto_id' => $esProducto ? $elemento->id : null,
            'servicio_id' => $esProducto ? null : $elemento->id,
            'descripcion' => $item['descripcion'],
            'cantidad' => $item['cantidad'],
            'precio_unitario' => $precio,
            'porcentaje_descuento' => $descuento,
            'subtotal' => $subtotal,
        ]);

        return $subtotal;
    }
}
