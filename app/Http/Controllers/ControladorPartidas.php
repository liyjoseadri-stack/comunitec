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
    public function guardar(Request $solicitud, Cotizacion $quote)
    {
        $datos = $this->validar($solicitud);
        $this->modificar($quote, fn () => $quote->lines()->create($datos));

        return back()->with('success', 'Partida agregada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    public function actualizar(Request $solicitud, Cotizacion $quote, PartidaCotizacion $line)
    {
        abort_unless($line->quote_id === $quote->id, 404);
        $datos = $this->validar($solicitud, $line);
        $this->modificar($quote, fn () => $line->update($datos));

        return back()->with('success', 'Partida actualizada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    public function eliminar(Cotizacion $quote, PartidaCotizacion $line)
    {
        abort_unless($line->quote_id === $quote->id, 404);
        $this->modificar($quote, fn () => $line->delete());

        return back()->with('success', 'Partida eliminada. Si la cotización estaba aceptada, requiere una nueva aceptación.');
    }

    private function validar(Request $solicitud, ?PartidaCotizacion $partida = null): array
    {
        $reglaArticulo = Rule::exists('catalog_items', 'id')
            ->where('type', $solicitud->input('type'));
        $conservaArticuloHistorico = $partida !== null
            && (int) $partida->catalog_item_id === (int) $solicitud->input('catalog_item_id');
        if (! $conservaArticuloHistorico) {
            $reglaArticulo->where('active', true);
        }

        $datos = $solicitud->validate([

            'type' => [
                'required',
                Rule::in([
                    'product',
                    'service',
                    'other',
                ]),
            ],

            'catalog_item_id' => [
                'required_unless:type,other',
                'nullable',
                $reglaArticulo,
            ],

            'description' => [
                'required',
                'string',
                'max:1000',
            ],

            'quantity' => [
                'required',
                $solicitud->input('type') === 'product' ? 'integer' : 'numeric',
                'gt:0',
            ],

            'unit_price' => [
                'required_if:type,other',
                'nullable',
                'numeric',
                'min:0',
            ],

        ]);
        if ($datos['type'] === 'other') {
            $datos['catalog_item_id'] = null;
        } else {
            $articulo = ArticuloCatalogo::findOrFail($datos['catalog_item_id']);
            $mismoArticulo = $partida !== null
                && (int) $partida->catalog_item_id === (int) $articulo->id;
            $datos['unit_price'] = $mismoArticulo
                ? $partida->unit_price
                : $articulo->price;
        }

        return [
            ...$datos,
            'subtotal' => round($datos['quantity'] * $datos['unit_price'],
                2),
        ];
    }

    private function modificar(Cotizacion $cotizacion, callable $operacion): void
    {
        DB::transaction(function () use ($cotizacion, $operacion) {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_if($actual->venta()->exists(), 422, 'Una cotización convertida en venta ya no admite cambios.');
            abort_unless(in_array($actual->status, [
                'draft',
                'pending',
                'accepted',
            ], true), 422, 'Esta cotización ya no admite cambios.');
            $operacion();
            if ($actual->status === 'accepted') {
                PiezaInventario::where('quote_id', $actual->id)->where('status', 'reserved')->update([
                    'status' => 'available',
                    'quote_id' => null,
                ]);
                $actual->fill([
                    'status' => 'pending',
                    'accepted_at' => null,
                    'expires_at' => now()->addDays(15),
                ]);
            }
            $actual->total = round($actual->lines()->sum('subtotal') * (1 - $actual->discount_percent / 100), 2);
            $actual->save();
        });
    }
}
