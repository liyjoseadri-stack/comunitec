<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use App\Models\Venta;
use App\Support\PeriodoReporte;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorReportes extends Controller
{
    public function cotizaciones(Request $solicitud): View
    {
        $datos = $solicitud->validate([
            'desde' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'hasta' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:desde',
            ],
            'cliente' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'responsable' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'estado' => [
                'nullable',
                Rule::in(Cotizacion::estados()),
            ],
        ]);
        $filtros = $this->completarFechas($datos);
        [$inicio, $finExclusivo] = $this->limites($filtros);

        $consulta = Cotizacion::query()
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<', $finExclusivo)
            ->when($filtros['cliente'] ?? null, fn ($query, $cliente) => $query->where(
                'customer_id',
                $cliente
            ))
            ->when($filtros['responsable'] ?? null, fn ($query, $responsable) => $query->where(
                'user_id',
                $responsable
            ))
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where(
                'status',
                $estado
            ));

        return view('reportes.cotizaciones', [
            'cotizaciones' => (clone $consulta)
                ->with('customer', 'responsable')
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
            'cantidadResultados' => (clone $consulta)->count(),
            'totalImporte' => (float) (clone $consulta)->sum('total'),
            'clientes' => Cliente::orderBy('name')->get(),
            'responsables' => Usuario::orderBy('name')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function ventas(Request $solicitud): View
    {
        $datos = $solicitud->validate([
            'desde' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'hasta' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:desde',
            ],
            'cliente' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'responsable' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'metodo_pago' => [
                'nullable',
                Rule::in(Venta::metodosPago()),
            ],
        ]);
        $filtros = $this->completarFechas($datos);
        [$inicio, $finExclusivo] = $this->limites($filtros);

        $consulta = Venta::query()
            ->where('sold_at', '>=', $inicio)
            ->where('sold_at', '<', $finExclusivo)
            ->when($filtros['cliente'] ?? null, fn ($query, $cliente) => $query->where(
                'customer_id',
                $cliente
            ))
            ->when($filtros['responsable'] ?? null, fn ($query, $responsable) => $query->where(
                'user_id',
                $responsable
            ))
            ->when($filtros['metodo_pago'] ?? null, fn ($query, $metodo) => $query->where(
                'payment_method',
                $metodo
            ));

        return view('reportes.ventas', [
            'ventas' => (clone $consulta)
                ->with('cotizacion', 'cliente', 'responsable')
                ->latest('sold_at')
                ->paginate(20)
                ->withQueryString(),
            'cantidadResultados' => (clone $consulta)->count(),
            'totalImporte' => (float) (clone $consulta)->sum('total'),
            'clientes' => Cliente::orderBy('name')->get(),
            'responsables' => Usuario::orderBy('name')->get(),
            'filtros' => $filtros,
        ]);
    }

    private function completarFechas(array $datos): array
    {
        $periodo = PeriodoReporte::desdeMes(null);
        $filtrosConValor = array_filter(
            $datos,
            fn ($valor): bool => $valor !== null && $valor !== ''
        );

        return array_merge([
            'desde' => $periodo->inicio->format('Y-m-d'),
            'hasta' => $periodo->finExclusivo->subDay()->format('Y-m-d'),
        ], $filtrosConValor);
    }

    private function limites(array $filtros): array
    {
        return [
            CarbonImmutable::createFromFormat('!Y-m-d', $filtros['desde']),
            CarbonImmutable::createFromFormat('!Y-m-d', $filtros['hasta'])->addDay(),
        ];
    }
}
