<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorServicios extends Controller
{
    public function listar(Request $solicitud): View
    {
        return view('inventario.servicios.listado', [
            'servicios' => Servicio::query()
                ->with('categoria')
                ->when($solicitud->filled('buscar'), function ($consulta) use ($solicitud) {
                    $buscar = '%'.$solicitud->string('buscar')->trim().'%';
                    $consulta->where(fn ($filtro) => $filtro
                        ->where('nombre', 'like', $buscar)
                        ->orWhere('codigo', 'like', $buscar));
                })
                ->when($solicitud->filled('categoria'), fn ($consulta) => $consulta
                    ->where('categoria_id', $solicitud->integer('categoria')))
                ->when($solicitud->input('estado') === 'activos', fn ($consulta) => $consulta->where('activo', true))
                ->when($solicitud->input('estado') === 'inactivos', fn ($consulta) => $consulta->where('activo', false))
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        Servicio::create($this->datosValidados($solicitud));

        return to_route('inventario.servicios.listado')->with('success', 'El servicio fue registrado.');
    }

    public function mostrar(Servicio $servicio): View
    {
        return view('inventario.servicios.detalle', [
            'servicio' => $servicio->load('categoria'),
            'categorias' => Categoria::where('activo', true)
                ->orWhereKey($servicio->categoria_id)
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function actualizar(Request $solicitud, Servicio $servicio): RedirectResponse
    {
        $servicio->update($this->datosValidados($solicitud, $servicio));

        return back()->with('success', 'El servicio fue actualizado.');
    }

    public function actualizarEstado(Servicio $servicio): RedirectResponse
    {
        $servicio->update(['activo' => ! $servicio->activo]);

        return back()->with('success', $servicio->activo
            ? 'El servicio fue reactivado.'
            : 'El servicio fue desactivado.');
    }

    private function datosValidados(Request $solicitud, ?Servicio $servicio = null): array
    {
        return $solicitud->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('servicios', 'codigo')->ignore($servicio)],
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where('activo', true)],
            'unidad' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
