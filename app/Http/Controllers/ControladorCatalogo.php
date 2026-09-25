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
            'items' => ArticuloCatalogo::with('category')->orderBy('name')->get(),
            'categories' => Categoria::where('active',
                true)->orderBy('name')->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'type' => [
                'required',
                'in:product,service',
            ],
            'name' => [
                'required',
            ],
            'code' => [
                'required',
                'unique:catalog_items,code',
            ],
            'category_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'brand' => [
                'nullable',
            ],
            'model' => [
                'nullable',
            ],
            'unit' => [
                'required',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'nullable',
                'required_if:type,product',
                'integer',
                'min:0',
            ],
        ]);

        if ($datos['type'] === 'service') {
            $datos['stock'] = 0;
        }

        ArticuloCatalogo::create($datos);

        return redirect()->route('catalogo.listado');
    }

    public function actualizarEstado(ArticuloCatalogo $item)
    {
        $item->update([
            'active' => ! $item->active,
        ]);

        return redirect()->route('catalogo.listado')->with(
            'success',
            $item->active ? 'El concepto fue reactivado.' : 'El concepto fue desactivado.'
        );
    }
}
