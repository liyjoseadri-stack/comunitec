<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Producto;
use App\Support\PlazosHabiles;
use Illuminate\View\View;

class ControladorPanel extends Controller
{
    private const LIMITE_ALERTAS = 5;

    public function mostrar(): View
    {
        $entregasPorVencer = collect();
        $productosStockBajo = collect();
        $totalEntregasPorVencer = 0;
        $totalProductosStockBajo = 0;

        if (auth()->user()->esAdministrador()) {
            if (today()->isWeekday()) {
                $siguienteDiaHabil = PlazosHabiles::sumar(today(), 1);
                $inicioSiguienteDiaHabil = $siguienteDiaHabil->copy()->startOfDay();
                $finSiguienteDiaHabil = $inicioSiguienteDiaHabil->copy()->addDay();
                $consultaEntregas = Cotizacion::query()
                    ->with('cliente')
                    ->where('estado', Cotizacion::ESTADO_ACEPTADA)
                    ->where('entrega_limite_en', '>=', $inicioSiguienteDiaHabil)
                    ->where('entrega_limite_en', '<', $finSiguienteDiaHabil);

                $totalEntregasPorVencer = (clone $consultaEntregas)->count();
                $entregasPorVencer = $consultaEntregas
                    ->orderBy('entrega_limite_en')
                    ->orderBy('id')
                    ->limit(self::LIMITE_ALERTAS)
                    ->get();
            }

            $consultaStockBajo = Producto::query()
                ->where('activo', true)
                ->where('existencias', '<=', 5);

            $totalProductosStockBajo = (clone $consultaStockBajo)->count();
            $productosStockBajo = $consultaStockBajo
                ->orderBy('existencias')
                ->orderBy('nombre')
                ->limit(self::LIMITE_ALERTAS)
                ->get();
        }

        return view('panel', [
            'entregasPorVencer' => $entregasPorVencer,
            'productosStockBajo' => $productosStockBajo,
            'totalEntregasPorVencer' => $totalEntregasPorVencer,
            'totalProductosStockBajo' => $totalProductosStockBajo,
        ]);
    }
}
