<?php

namespace App\Http\Controllers;

use App\Models\ArticuloCatalogo;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PiezaInventario;
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
        $reglaArticulo = Rule::exists('articulos_catalogo', 'id')
            ->where('tipo', $solicitud->input('tipo'));
        $conservaArticuloHistorico = $partida !== null
            && (int) $partida->articulo_catalogo_id === (int) $solicitud->input('articulo_catalogo_id');
        if (! $conservaArticuloHistorico) {
            $reglaArticulo->where('activo', true);
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

            'articulo_catalogo_id' => [
                'required_unless:tipo,otro',
                'nullable',
                $reglaArticulo,
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

        ]);
        if ($datos['tipo'] === 'otro') {
            $datos['articulo_catalogo_id'] = null;
        } else {
            $articulo = ArticuloCatalogo::findOrFail($datos['articulo_catalogo_id']);
            $mismoArticulo = $partida !== null
                && (int) $partida->articulo_catalogo_id === (int) $articulo->id;
            $datos['precio_unitario'] = $mismoArticulo
                ? $partida->precio_unitario
                : $articulo->precio;
        }

        return [
            ...$datos,
            'subtotal' => round($datos['cantidad'] * $datos['precio_unitario'],
                2),
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
            $operacion();
            if ($actual->estado === 'aceptada') {
                PiezaInventario::where('cotizacion_id', $actual->id)->where('estado', 'reservada')->update([
                    'estado' => 'disponible',
                    'cotizacion_id' => null,
                ]);
                $actual->fill([
                    'estado' => 'pendiente',
                    'aceptada_en' => null,
                    'vence_en' => now()->addDays(15),
                ]);
            }
            $actual->total = round($actual->partidas()->sum('subtotal') * (1 - $actual->porcentaje_descuento / 100), 2);
            $actual->save();
        });
    }
}
