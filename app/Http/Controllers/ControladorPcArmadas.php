<?php

namespace App\Http\Controllers;

use App\Models\PcArmada;
use App\Models\Producto;
use App\Services\ServicioConfiguracionPc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorPcArmadas extends Controller
{
    public function listar(Request $solicitud): View
    {
        return view('inventario.pc-armadas.listado', [
            'pcArmadas' => PcArmada::with('componentes.producto')
                ->when($solicitud->filled('buscar'), fn ($consulta) => $consulta
                    ->where('nombre', 'like', '%'.$solicitud->string('buscar')->trim().'%'))
                ->when($solicitud->input('estado') === 'activas', fn ($consulta) => $consulta->where('activo', true))
                ->when($solicitud->input('estado') === 'inactivas', fn ($consulta) => $consulta->where('activo', false))
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function crear(): View
    {
        return view('inventario.pc-armadas.crear', $this->datosFormulario());
    }

    public function guardar(Request $solicitud, ServicioConfiguracionPc $servicio): RedirectResponse
    {
        $plantilla = $servicio->crearPlantilla($this->datosValidados($solicitud));

        return to_route('inventario.pc-armadas.detalle', $plantilla)
            ->with('success', 'La PC armada fue registrada.');
    }

    public function mostrar(PcArmada $pcArmada): View
    {
        return view('inventario.pc-armadas.detalle', [
            'pcArmada' => $pcArmada->load('componentes.producto'),
        ]);
    }

    public function editar(PcArmada $pcArmada): View
    {
        return view('inventario.pc-armadas.editar', [
            ...$this->datosFormulario(),
            'pcArmada' => $pcArmada->load('componentes.producto'),
        ]);
    }

    public function actualizar(
        Request $solicitud,
        PcArmada $pcArmada,
        ServicioConfiguracionPc $servicio
    ): RedirectResponse {
        $servicio->actualizarPlantilla($pcArmada, $this->datosValidados($solicitud, $pcArmada));

        return to_route('inventario.pc-armadas.detalle', $pcArmada)
            ->with('success', 'La PC armada fue actualizada.');
    }

    public function actualizarEstado(PcArmada $pcArmada): RedirectResponse
    {
        $pcArmada->update(['activo' => ! $pcArmada->activo]);

        return back()->with('success', $pcArmada->activo
            ? 'La PC armada fue activada.'
            : 'La PC armada fue desactivada.');
    }

    private function datosFormulario(): array
    {
        return [
            'productos' => Producto::query()
                ->where('activo', true)
                ->whereNotNull('tipo_componente_pc')
                ->orderBy('nombre')
                ->get(),
            'tiposComponentesPc' => Producto::etiquetasComponentesPc(),
        ];
    }

    private function datosValidados(Request $solicitud, ?PcArmada $pcArmada = null): array
    {
        $datos = $solicitud->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pc_armadas', 'nombre')->ignore($pcArmada),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'componentes' => ['required', 'array', 'min:1', 'max:100'],
            'componentes.*.producto_id' => [
                'nullable',
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'componentes.*.tipo_componente_pc' => ['required', Rule::in(Producto::tiposComponentesPc())],
            'componentes.*.cantidad' => ['nullable', 'integer', 'min:1'],
        ]);

        $datos['componentes'] = array_values(array_filter(
            $datos['componentes'],
            fn (array $componente): bool => filled($componente['producto_id'] ?? null)
        ));

        return $datos;
    }
}
