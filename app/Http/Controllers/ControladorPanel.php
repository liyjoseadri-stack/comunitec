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
            ->where('creado_en', '>=', $periodo->inicio)
            ->where('creado_en', '<', $periodo->finExclusivo);
        $conteos = (clone $consultaCotizaciones)
            ->selectRaw('estado, COUNT(*) AS cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado');
        $resumenCotizaciones = collect([
            'borrador',
            'pendiente',
            'aceptada',
            'rechazada',
            'cancelada',
            'vencida',
        ])->mapWithKeys(fn (string $estado): array => [
            $estado => (int) ($conteos[$estado] ?? 0),
        ])->all();
        $totalCotizaciones = array_sum($resumenCotizaciones);
        $porcentajeAceptacion = $totalCotizaciones === 0
            ? 0.0
            : round($resumenCotizaciones['aceptada'] * 100 / $totalCotizaciones, 1);

        $consultaVentas = Venta::query()
            ->where('vendida_en', '>=', $periodo->inicio)
            ->where('vendida_en', '<', $periodo->finExclusivo);

        return view('panel', [
            'periodo' => $periodo,
            'totalCotizaciones' => $totalCotizaciones,
            'resumenCotizaciones' => $resumenCotizaciones,
            'porcentajeAceptacion' => $porcentajeAceptacion,
            'cantidadVentas' => (clone $consultaVentas)->count(),
            'totalVentas' => (float) (clone $consultaVentas)->sum('total'),
            'cotizacionesRecientes' => (clone $consultaCotizaciones)
                ->with('cliente')
                ->latest('creado_en')
                ->limit(5)
                ->get(),
            'ventasRecientes' => (clone $consultaVentas)
                ->with('cliente')
                ->latest('vendida_en')
                ->limit(5)
                ->get(),
            'productosStockBajo' => $this->productosConStockBajo(),
        ]);
    }

    private function productosConStockBajo()
    {
        return ArticuloCatalogo::where('tipo', 'producto')->withCount([
            'piezasInventario as cantidad_piezas_disponibles' => fn ($consulta) => $consulta->where(
                'estado',
                'disponible'
            ),
        ])->get()
            ->filter(fn ($articulo) => $articulo->cantidad_piezas_disponibles <= 5)
            ->sortBy('cantidad_piezas_disponibles');
    }
}
