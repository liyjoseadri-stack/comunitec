<?php

namespace App\Http\Controllers;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use App\Models\PiezaInventario;
use Illuminate\Http\Request;

class ControladorInventario extends Controller
{
    public function listar()
    {
        return view('inventario.listado', [
            'categories' => Categoria::all(),
            'products' => ArticuloCatalogo::where('type',
                'product')->get(),
            'units' => PiezaInventario::with(
                'item',
                'partidaVenta.venta'
            )->latest()->get(),
        ]);
    }

    public function guardarCategoria(Request $r)
    {
        Categoria::create($r->validate([
            'name' => [
                'required',
                'unique:categories,name',
            ],
        ]));

        return back();
    }

    public function registrarPieza(Request $r)
    {
        PiezaInventario::create($r->validate([
            'catalog_item_id' => [
                'required',
                'exists:catalog_items,id',
            ],
            'serial_number' => [
                'required',
                'unique:inventory_units,serial_number',
            ],
        ]));

        return back();
    }
}
