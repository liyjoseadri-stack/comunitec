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
    public function listar(Request $solicitud): View
    {
        $busqueda = trim($solicitud->string('buscar')->toString());
        $metodoPago = $solicitud->string('metodo_pago')->toString();
        $metodoPago = in_array($metodoPago, Venta::metodosPago(), true) ? $metodoPago : '';

        $ventas = Venta::query()
            ->with('cotizacion', 'cliente')
            ->when($busqueda, fn ($consulta) => $consulta->where(function ($consulta) use ($busqueda) {
                $consulta
                    ->where('folio', 'like', "%{$busqueda}%")
                    ->orWhere('nombre_cliente', 'like', "%{$busqueda}%")
                    ->orWhere('rfc_cliente', 'like', "%{$busqueda}%")
                    ->orWhere('correo_cliente', 'like', "%{$busqueda}%")
                    ->orWhereHas('cotizacion', fn ($cotizaciones) => $cotizaciones
                        ->where('folio', 'like', "%{$busqueda}%"));
            }))
            ->when($metodoPago, fn ($consulta) => $consulta->where('metodo_pago', $metodoPago))
            ->latest('vendida_en')
            ->paginate(15)
            ->withQueryString();

        return view('ventas.listado', [
            'ventas' => $ventas,
            'filtros' => [
                'buscar' => $busqueda,
                'metodo_pago' => $metodoPago,
            ],
        ]);
    }

    public function registrar(): View
    {
        $consultaAceptadas = Cotizacion::query()->convertiblesEnVenta();

        return view('ventas.registrar', [
            'totalCotizacionesAceptadas' => (clone $consultaAceptadas)->count(),
            'cotizacionesAceptadas' => $consultaAceptadas
                ->with('cliente')
                ->latest('aceptada_en')
                ->limit(10)
                ->get(),
        ]);
    }

    public function crear(Cotizacion $cotizacion): View
    {
        abort_unless($cotizacion->puedeConvertirseEnVenta(), 422);

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
