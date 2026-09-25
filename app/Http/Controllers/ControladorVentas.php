<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Venta;
use App\Services\ServicioConversionVenta;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'cotizacionesAceptadas' => auth()->user()->esAdministrador() || auth()->user()->esComercial()
                ? Cotizacion::with('cliente')->where('estado', Cotizacion::ESTADO_ACEPTADA)
                    ->latest('aceptada_en')->get()
                : collect(),
        ]);
    }

    public function crear(Cotizacion $cotizacion): View
    {
        abort_unless($cotizacion->estado === Cotizacion::ESTADO_ACEPTADA && ! $cotizacion->venta()->exists(), 422);

        return view('ventas.crear', [
            'cotizacion' => $cotizacion->load('cliente', 'partidas.producto', 'partidas.servicio'),
        ]);
    }

    public function mostrar(Venta $venta): View
    {
        return view('ventas.detalle', [
            'venta' => $venta->load(
                'cotizacion',
                'cliente',
                'responsable',
                'partidas.producto',
                'partidas.servicio',
                'partidas.piezas'
            ),
        ]);
    }

    public function pdf(Venta $venta)
    {
        return Pdf::loadView('ventas.pdf', [
            'venta' => $venta->load(
                'cotizacion',
                'cliente',
                'responsable',
                'partidas.producto',
                'partidas.servicio',
                'partidas.piezas'
            ),
        ])->download("{$venta->folio}.pdf");
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
                'nullable',
                'string',
                'max:255',
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
