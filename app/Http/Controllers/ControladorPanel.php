<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Producto;
use App\Support\PlazosHabiles;
use Illuminate\View\View;

class ControladorPanel extends Controller
{
    public function mostrar(): View
    {
        return view('panel', [
            'entregasPorVencer' => auth()->user()->esAdministrador()
                ? Cotizacion::with('cliente')
                    ->where('estado', Cotizacion::ESTADO_ACEPTADA)
                    ->whereNotNull('entrega_limite_en')
                    ->get()
                    ->filter(fn (Cotizacion $cotizacion): bool => PlazosHabiles::diaHabilAnterior(
                        $cotizacion->entrega_limite_en
                    )->isSameDay(today()))
                : collect(),
            'productosStockBajo' => auth()->user()->esAdministrador()
                ? Producto::where('activo', true)
                    ->where('existencias', '<=', 5)
                    ->orderBy('existencias')
                    ->get()
                : collect(),
        ]);
    }
}
