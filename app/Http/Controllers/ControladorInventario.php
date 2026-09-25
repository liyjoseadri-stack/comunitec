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
            'categorias' => Categoria::all(),
            'productos' => ArticuloCatalogo::where('tipo', 'producto')->get(),
            'piezas' => PiezaInventario::with(
                'articulo',
                'partidaVenta.venta.cliente'
            )->latest('creado_en')->get(),
        ]);
    }

    public function guardarCategoria(Request $r)
    {
        Categoria::create($r->validate([
            'nombre' => [
                'required',
                'unique:categorias,nombre',
            ],
        ]));

        return back();
    }

    public function registrarPieza(Request $r)
    {
        PiezaInventario::create($r->validate([
            'articulo_catalogo_id' => [
                'required',
                'exists:articulos_catalogo,id',
            ],
            'numero_serie' => [
                'required',
                'unique:piezas_inventario,numero_serie',
            ],
        ]));

        return back();
    }
}
