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
                'exists:clientes,id',
            ],
            'responsable' => [
                'nullable',
                'integer',
                'exists:usuarios,id',
            ],
            'estado' => [
                'nullable',
                Rule::in(Cotizacion::estados()),
            ],
        ]);
        $filtros = $this->completarFechas($datos);
        [$inicio, $finExclusivo] = $this->limites($filtros);

        $consulta = Cotizacion::query()
            ->where('creado_en', '>=', $inicio)
            ->where('creado_en', '<', $finExclusivo)
            ->when($filtros['cliente'] ?? null, fn ($consulta, $cliente) => $consulta->where(
                'cliente_id',
                $cliente
            ))
            ->when($filtros['responsable'] ?? null, fn ($consulta, $responsable) => $consulta->where(
                'usuario_id',
                $responsable
            ))
            ->when($filtros['estado'] ?? null, fn ($consulta, $estado) => $consulta->where(
                'estado',
                $estado
            ));

        return view('reportes.cotizaciones', [
            'cotizaciones' => (clone $consulta)
                ->with('cliente', 'responsable')
                ->latest('creado_en')
                ->paginate(20)
                ->withQueryString(),
            'cantidadResultados' => (clone $consulta)->count(),
            'totalImporte' => (float) (clone $consulta)->sum('total'),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'responsables' => Usuario::orderBy('nombre')->get(),
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
                'exists:clientes,id',
            ],
            'responsable' => [
                'nullable',
                'integer',
                'exists:usuarios,id',
            ],
            'metodo_pago' => [
                'nullable',
                Rule::in(Venta::metodosPago()),
            ],
        ]);
        $filtros = $this->completarFechas($datos);
        [$inicio, $finExclusivo] = $this->limites($filtros);

        $consulta = Venta::query()
            ->where('vendida_en', '>=', $inicio)
            ->where('vendida_en', '<', $finExclusivo)
            ->when($filtros['cliente'] ?? null, fn ($consulta, $cliente) => $consulta->where(
                'cliente_id',
                $cliente
            ))
            ->when($filtros['responsable'] ?? null, fn ($consulta, $responsable) => $consulta->where(
                'usuario_id',
                $responsable
            ))
            ->when($filtros['metodo_pago'] ?? null, fn ($consulta, $metodo) => $consulta->where(
                'metodo_pago',
                $metodo
            ));

        return view('reportes.ventas', [
            'ventas' => (clone $consulta)
                ->with('cotizacion', 'cliente', 'responsable')
                ->latest('vendida_en')
                ->paginate(20)
                ->withQueryString(),
            'cantidadResultados' => (clone $consulta)->count(),
            'totalImporte' => (float) (clone $consulta)->sum('total'),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'responsables' => Usuario::orderBy('nombre')->get(),
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
