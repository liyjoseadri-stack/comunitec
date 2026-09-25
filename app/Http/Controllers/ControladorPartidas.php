<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ControladorPartidas extends Controller
{
    public function guardar(Request $solicitud, Cotizacion $cotizacion)
    {
        $datos = $this->validar($solicitud);
        $this->modificar($cotizacion, fn () => $cotizacion->partidas()->create($datos));

        return back()->with('success', 'Partida agregada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    public function actualizar(Request $solicitud, Cotizacion $cotizacion, PartidaCotizacion $partida)
    {
        abort_unless($partida->cotizacion_id === $cotizacion->id, 404);
        $datos = $this->validar($solicitud, $partida);
        $this->modificar($cotizacion, fn () => $partida->update($datos));

        return back()->with('success', 'Partida actualizada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    public function eliminar(Cotizacion $cotizacion, PartidaCotizacion $partida)
    {
        abort_unless($partida->cotizacion_id === $cotizacion->id, 404);
        $this->modificar($cotizacion, fn () => $partida->delete());

        return back()->with('success', 'Partida eliminada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    private function validar(Request $solicitud, ?PartidaCotizacion $partida = null): array
    {
        if (! $solicitud->filled('porcentaje_descuento')) {
            $solicitud->merge([
                'porcentaje_descuento' => $partida?->porcentaje_descuento ?? 0,
            ]);
        }

        if (! $solicitud->filled('tipo')) {
            $tipo = $solicitud->filled('producto_id')
                ? 'producto'
                : ($solicitud->filled('servicio_id') ? 'servicio' : $partida?->tipo);
            $solicitud->merge(['tipo' => $tipo]);
        }

        $conservaProducto = $partida !== null
            && (int) $partida->producto_id === (int) $solicitud->input('producto_id');
        $conservaServicio = $partida !== null
            && (int) $partida->servicio_id === (int) $solicitud->input('servicio_id');
        $reglaProducto = Rule::exists('productos', 'id');
        $reglaServicio = Rule::exists('servicios', 'id');
        if (! $conservaProducto) {
            $reglaProducto->where('activo', true);
        }
        if (! $conservaServicio) {
            $reglaServicio->where('activo', true);
        }

        $datos = $solicitud->validate([

            'tipo' => [
                'required',
                Rule::in([
                    'producto',
                    'servicio',
                    'otro',
                ]),
            ],

            'producto_id' => [
                'required_if:tipo,producto',
                'nullable',
                $reglaProducto,
            ],

            'servicio_id' => [
                'required_if:tipo,servicio',
                'nullable',
                $reglaServicio,
            ],

            'descripcion' => [
                'required',
                'string',
                'max:1000',
            ],

            'cantidad' => [
                'required',
                $solicitud->input('tipo') === 'producto' ? 'integer' : 'numeric',
                'gt:0',
            ],

            'precio_unitario' => [
                'required_if:tipo,otro',
                'nullable',
                'numeric',
                'min:0',
            ],

            'porcentaje_descuento' => [
                'required',
                'numeric',
                'between:0,100',
            ],

        ]);
        if ($datos['tipo'] === 'otro') {
            $datos['producto_id'] = null;
            $datos['servicio_id'] = null;
        } else {
            $esProducto = $datos['tipo'] === 'producto';
            $elemento = $esProducto
                ? Producto::findOrFail($datos['producto_id'])
                : Servicio::findOrFail($datos['servicio_id']);
            $mismoElemento = $esProducto ? $conservaProducto : $conservaServicio;
            $datos['producto_id'] = $esProducto ? $elemento->id : null;
            $datos['servicio_id'] = $esProducto ? null : $elemento->id;
            $datos['precio_unitario'] = $mismoElemento
                ? $partida->precio_unitario
                : $elemento->precio;
        }

        return [
            ...$datos,
            'subtotal' => round(
                $datos['cantidad']
                    * $datos['precio_unitario']
                    * (1 - $datos['porcentaje_descuento'] / 100),
                2
            ),
        ];
    }

    private function modificar(Cotizacion $cotizacion, callable $operacion): void
    {
        DB::transaction(function () use ($cotizacion, $operacion) {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_if($actual->venta()->exists(), 422, 'Una cotización convertida en venta ya no admite cambios.');
            abort_unless(in_array($actual->estado, [
                'borrador',
                'pendiente',
                'aceptada',
            ], true), 422, 'Esta cotización ya no admite cambios.');
            if ($actual->estado === 'aceptada') {
                app(ServicioInventarioCotizacion::class)->liberarParaEdicion($actual);
            }
            $operacion();
            $actual->total = round($actual->partidas()->sum('subtotal') * (1 - $actual->porcentaje_descuento / 100), 2);
            $actual->save();
        });
    }
}
