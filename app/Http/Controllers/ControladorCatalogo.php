<?php

namespace App\Http\Controllers;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ControladorCatalogo extends Controller
{
    public function listar()
    {
        return view('catalogo.listado', [
            'articulos' => ArticuloCatalogo::with('categoria')->orderBy('nombre')->get(),
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'tipo' => [
                'required',
                'in:producto,servicio',
            ],
            'nombre' => [
                'required',
            ],
            'codigo' => [
                'required',
                'unique:articulos_catalogo,codigo',
            ],
            'categoria_id' => [
                'nullable',
                'exists:categorias,id',
            ],
            'marca' => [
                'nullable',
            ],
            'modelo' => [
                'nullable',
            ],
            'unidad' => [
                'required',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0',
            ],
            'existencias' => [
                'nullable',
                'required_if:tipo,producto',
                'integer',
                'min:0',
            ],
        ]);

        if ($datos['tipo'] === 'servicio') {
            $datos['existencias'] = 0;
        }

        ArticuloCatalogo::create($datos);

        return redirect()->route('catalogo.listado');
    }

    public function actualizarEstado(ArticuloCatalogo $articulo)
    {
        $articulo->update([
            'activo' => ! $articulo->activo,
        ]);

        return redirect()->route('catalogo.listado')->with(
            'success',
            $articulo->activo ? 'El concepto fue reactivado.' : 'El concepto fue desactivado.'
        );
    }
}
