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
        ArticuloCatalogo::create($request->validate([
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
                'required_if:type,product',
                'integer',
                'min:0',
            ],
        ]));

        return redirect()->route('catalogo.listado');
    }
}
