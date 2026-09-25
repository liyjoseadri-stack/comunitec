<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Venta;
use App\Services\ServicioConversionVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorVentas extends Controller
{
    public function listar(): View
    {
        return view('ventas.listado', [
            'ventas' => Venta::with('cotizacion', 'cliente')
                ->latest('vendida_en')
                ->get(),
        ]);
    }

    public function mostrar(Venta $venta): View
    {
        return view('ventas.detalle', [
            'venta' => $venta->load(
                'cotizacion',
                'cliente',
                'responsable',
                'partidas.piezas'
            ),
        ]);
    }

    public function guardar(
        Request $solicitud,
        Cotizacion $cotizacion,
        ServicioConversionVenta $conversion
    ): RedirectResponse {
        $datos = $solicitud->validate([
            'metodo_pago' => [
                'required',
                Rule::in(Venta::metodosPago()),
            ],
            'detalle_metodo_pago' => [
                'nullable',
                'required_if:metodo_pago,'.Venta::METODO_OTRO,
                'string',
                'max:255',
            ],
            'series' => [
                'nullable',
                'array',
            ],
            'series.*' => [
                'array',
            ],
            'series.*.*' => [
                'integer',
                'distinct',
            ],
        ]);

        $venta = $conversion->convertir(
            $cotizacion,
            $solicitud->user(),
            $datos['metodo_pago'],
            $datos['detalle_metodo_pago'] ?? null,
            $datos['series'] ?? []
        );

        return redirect()
            ->route('ventas.detalle', $venta)
            ->with('success', "Se creó la venta {$venta->folio}.");
    }
}
