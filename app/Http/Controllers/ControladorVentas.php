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
                ->latest('sold_at')
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
        Cotizacion $quote,
        ServicioConversionVenta $conversion
    ): RedirectResponse {
        $datos = $solicitud->validate([
            'payment_method' => [
                'required',
                Rule::in(Venta::metodosPago()),
            ],
            'payment_method_detail' => [
                'nullable',
                'required_if:payment_method,'.Venta::METODO_OTRO,
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
            $quote,
            $solicitud->user(),
            $datos['payment_method'],
            $datos['payment_method_detail'] ?? null,
            $datos['series'] ?? []
        );

        return redirect()
            ->route('ventas.detalle', $venta)
            ->with('success', "Se creó la venta {$venta->folio}.");
    }
}
