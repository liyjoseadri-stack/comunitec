<?php

namespace App\Http\Controllers;

use App\Models\ArticuloCatalogo;
use App\Models\Cotizacion;
use App\Models\Venta;
use App\Support\PeriodoReporte;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorPanel extends Controller
{
    public function mostrar(Request $solicitud): View
    {
        $datos = $solicitud->validate([
            'mes' => [
                'nullable',
                'date_format:Y-m',
            ],
        ], [
            'mes.date_format' => 'Selecciona un mes válido.',
        ]);
        $periodo = PeriodoReporte::desdeMes($datos['mes'] ?? null);

        $consultaCotizaciones = Cotizacion::query()
            ->where('created_at', '>=', $periodo->inicio)
            ->where('created_at', '<', $periodo->finExclusivo);
        $conteos = (clone $consultaCotizaciones)
            ->selectRaw('status, COUNT(*) AS cantidad')
            ->groupBy('status')
            ->pluck('cantidad', 'status');
        $resumenCotizaciones = collect([
            'draft',
            'pending',
            'accepted',
            'rejected',
            'cancelled',
            'expired',
        ])->mapWithKeys(fn (string $estado): array => [
            $estado => (int) ($conteos[$estado] ?? 0),
        ])->all();
        $totalCotizaciones = array_sum($resumenCotizaciones);
        $porcentajeAceptacion = $totalCotizaciones === 0
            ? 0.0
            : round($resumenCotizaciones['accepted'] * 100 / $totalCotizaciones, 1);

        $consultaVentas = Venta::query()
            ->where('sold_at', '>=', $periodo->inicio)
            ->where('sold_at', '<', $periodo->finExclusivo);

        return view('panel', [
            'periodo' => $periodo,
            'totalCotizaciones' => $totalCotizaciones,
            'resumenCotizaciones' => $resumenCotizaciones,
            'porcentajeAceptacion' => $porcentajeAceptacion,
            'cantidadVentas' => (clone $consultaVentas)->count(),
            'totalVentas' => (float) (clone $consultaVentas)->sum('total'),
            'cotizacionesRecientes' => (clone $consultaCotizaciones)
                ->with('customer')
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'ventasRecientes' => (clone $consultaVentas)
                ->with('cliente')
                ->latest('sold_at')
                ->limit(5)
                ->get(),
            'lowStock' => $this->productosConStockBajo(),
        ]);
    }

    private function productosConStockBajo()
    {
        return ArticuloCatalogo::where('type', 'product')->withCount([
            'inventoryUnits as available_units_count' => fn ($query) => $query->where(
                'status',
                'available'
            ),
        ])->get()
            ->filter(fn ($articulo) => $articulo->available_units_count <= 5)
            ->sortBy('available_units_count');
    }
}
