<?php

namespace App\Http\Controllers;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use App\Models\PiezaInventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorProductos extends Controller
{
    public function listar(Request $solicitud): View
    {
        $articulos = ArticuloCatalogo::query()->with('categoria')
            ->when($solicitud->filled('buscar'), function ($consulta) use ($solicitud) {
                $buscar = '%'.$solicitud->string('buscar')->trim().'%';
                $consulta->where(fn ($q) => $q->where('nombre', 'like', $buscar)
                    ->orWhere('codigo', 'like', $buscar)->orWhere('marca', 'like', $buscar)
                    ->orWhere('modelo', 'like', $buscar));
            })
            ->when($solicitud->filled('categoria'), fn ($q) => $q->where('categoria_id', $solicitud->integer('categoria')))
            ->when(in_array($solicitud->input('tipo'), ['producto', 'servicio'], true), fn ($q) => $q->where('tipo', $solicitud->input('tipo')))
            ->when($solicitud->input('estado') === 'activos', fn ($q) => $q->where('activo', true))
            ->when($solicitud->input('estado') === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('nombre')->paginate(15)->withQueryString();

        return view('inventario.productos.listado', [
            'articulos' => $articulos,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'seriesVendidas' => PiezaInventario::whereNotNull('partida_venta_id')
                ->with('articulo', 'partidaVenta.venta.cliente')
                ->latest('creado_en')->limit(50)->get(),
        ]);
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        ArticuloCatalogo::create($this->datosValidados($solicitud));

        return redirect()->route('inventario.listado')->with('success', 'El artículo fue registrado.');
    }

    public function mostrar(ArticuloCatalogo $articulo): View
    {
        return view('inventario.productos.detalle', [
            'articulo' => $articulo->load('categoria', 'seriesVendidas.partidaVenta.venta'),
            'categorias' => Categoria::where('activo', true)->orWhereKey($articulo->categoria_id)->orderBy('nombre')->get(),
        ]);
    }

    public function actualizar(Request $solicitud, ArticuloCatalogo $articulo): RedirectResponse
    {
        $articulo->update($this->datosValidados($solicitud, $articulo));

        return back()->with('success', 'El artículo fue actualizado.');
    }

    public function actualizarEstado(ArticuloCatalogo $articulo): RedirectResponse
    {
        $articulo->update(['activo' => ! $articulo->activo]);

        return back()->with('success', $articulo->activo ? 'El artículo fue reactivado.' : 'El artículo fue desactivado.');
    }

    private function datosValidados(Request $solicitud, ?ArticuloCatalogo $articulo = null): array
    {
        $datos = $solicitud->validate([
            'tipo' => ['required', Rule::in(['producto', 'servicio'])],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('articulos_catalogo', 'codigo')->ignore($articulo)],
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where('activo', true)],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
            'existencias' => ['nullable', 'required_if:tipo,producto', 'integer', 'min:0'],
            'requiere_numero_serie' => ['nullable', 'boolean'],
        ]);
        $datos['requiere_numero_serie'] = $datos['tipo'] === 'producto'
            && $solicitud->boolean('requiere_numero_serie');
        if ($datos['tipo'] === 'servicio') {
            $datos['existencias'] = 0;
        }

        return $datos;
    }
}
