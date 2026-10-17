<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Rules\NumeroFinito;
use App\Services\ServicioCreacionCotizacion;
use App\Services\ServicioInventarioCotizacion;
use App\Support\CalculosCotizacion;
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
                ? PartidaCotizacion::TIPO_PRODUCTO
                : ($solicitud->filled('servicio_id') ? PartidaCotizacion::TIPO_SERVICIO : $partida?->tipo);
            $solicitud->merge(['tipo' => $tipo]);
        }

        $conservaProducto = $partida !== null
            && $partida->tipo === PartidaCotizacion::TIPO_PRODUCTO
            && $solicitud->input('tipo') === PartidaCotizacion::TIPO_PRODUCTO
            && (int) $partida->producto_id === (int) $solicitud->input('producto_id');
        $conservaServicio = $partida !== null
            && $partida->tipo === PartidaCotizacion::TIPO_SERVICIO
            && $solicitud->input('tipo') === PartidaCotizacion::TIPO_SERVICIO
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
                Rule::in(PartidaCotizacion::tipos()),
            ],

            'producto_id' => [
                Rule::requiredIf($solicitud->input('tipo') === PartidaCotizacion::TIPO_PRODUCTO && ! $conservaProducto),
                'nullable',
                $reglaProducto,
            ],

            'servicio_id' => [
                Rule::requiredIf($solicitud->input('tipo') === PartidaCotizacion::TIPO_SERVICIO && ! $conservaServicio),
                'nullable',
                $reglaServicio,
            ],

            'descripcion' => [
                'required',
                'string',
                'max:1000',
            ],

            'cantidad' => [
                'bail',
                'required',
                new NumeroFinito,
                $solicitud->input('tipo') === PartidaCotizacion::TIPO_PRODUCTO ? 'integer' : 'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                'gt:0',
            ],

            'precio_unitario' => [
                'bail',
                'required_if:tipo,'.PartidaCotizacion::TIPO_OTRO,
                'nullable',
                new NumeroFinito,
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                'min:0',
            ],

            'porcentaje_descuento' => [
                'bail',
                'required',
                new NumeroFinito,
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                'between:0,100',
            ],

        ]);
        if ($datos['tipo'] === PartidaCotizacion::TIPO_OTRO) {
            $datos['producto_id'] = null;
            $datos['servicio_id'] = null;
            $datos = [...$datos,
                'nombre_concepto' => $datos['descripcion'],
                'codigo_concepto' => null,
                'marca_concepto' => null,
                'modelo_concepto' => null,
                'unidad_concepto' => null,
                'requiere_numero_serie' => false,
            ];
        } else {
            $esProducto = $datos['tipo'] === PartidaCotizacion::TIPO_PRODUCTO;
            $mismoElemento = $esProducto ? $conservaProducto : $conservaServicio;
            $elemento = $esProducto
                ? Producto::find($datos['producto_id'] ?? null)
                : Servicio::find($datos['servicio_id'] ?? null);
            if (! $mismoElemento) {
                abort_if($elemento === null, 422);
                $datos = [...$datos, ...app(ServicioCreacionCotizacion::class)->snapshot($elemento)];
            }
            $datos['producto_id'] = $esProducto ? $elemento?->id : null;
            $datos['servicio_id'] = $esProducto ? null : $elemento?->id;
            $datos['precio_unitario'] = $mismoElemento
                ? $partida->precio_unitario
                : $elemento->precio;
        }

        return [
            ...$datos,
            'subtotal' => CalculosCotizacion::descontar(
                $datos['cantidad'] * $datos['precio_unitario'],
                $datos['porcentaje_descuento']
            ),
        ];
    }

    private function modificar(Cotizacion $cotizacion, callable $operacion): void
    {
        DB::transaction(function () use ($cotizacion, $operacion) {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_if($actual->venta()->exists(), 422, 'Una cotización convertida en venta ya no admite cambios.');
            abort_unless(in_array($actual->estado, [
                Cotizacion::ESTADO_BORRADOR,
                Cotizacion::ESTADO_PENDIENTE,
                Cotizacion::ESTADO_ACEPTADA,
            ], true), 422, 'Esta cotización ya no admite cambios.');
            if ($actual->estado === Cotizacion::ESTADO_ACEPTADA) {
                app(ServicioInventarioCotizacion::class)->liberarParaEdicion($actual);
            }
            $operacion();
            $actual->total = CalculosCotizacion::descontar(
                $actual->partidas()->sum('subtotal'),
                $actual->porcentaje_descuento
            );
            $actual->save();
        });
    }
}
