<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorCategorias extends Controller
{
    public function listar(Request $solicitud): View
    {
        $categorias = Categoria::query()
            ->withCount('articulos')
            ->when($solicitud->filled('buscar'), fn ($consulta) => $consulta
                ->where('nombre', 'like', '%'.$solicitud->string('buscar')->trim().'%'))
            ->when($solicitud->input('estado') === 'activas', fn ($consulta) => $consulta->where('activo', true))
            ->when($solicitud->input('estado') === 'inactivas', fn ($consulta) => $consulta->where('activo', false))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('inventario.categorias.listado', compact('categorias'));
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        Categoria::create($solicitud->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
        ]));

        return back()->with('success', 'La categoría fue registrada.');
    }

    public function mostrar(Categoria $categoria): View
    {
        return view('inventario.categorias.detalle', [
            'categoria' => $categoria->load(['articulos' => fn ($consulta) => $consulta->orderBy('nombre')]),
        ]);
    }

    public function actualizar(Request $solicitud, Categoria $categoria): RedirectResponse
    {
        $categoria->update($solicitud->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nombre')->ignore($categoria)],
        ]));

        return back()->with('success', 'La categoría fue actualizada.');
    }

    public function actualizarEstado(Categoria $categoria): RedirectResponse
    {
        $categoria->update(['activo' => ! $categoria->activo]);

        return back()->with('success', $categoria->activo
            ? 'La categoría fue reactivada.'
            : 'La categoría fue desactivada.');
    }
}
