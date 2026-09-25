<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\PiezaInventario;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorProductos extends Controller
{
    public function listar(Request $solicitud): View
    {
        return view('inventario.productos.listado', [
            'productos' => Producto::query()
                ->with('categoria')
                ->when($solicitud->filled('buscar'), function ($consulta) use ($solicitud) {
                    $buscar = '%'.$solicitud->string('buscar')->trim().'%';
                    $consulta->where(fn ($filtro) => $filtro
                        ->where('nombre', 'like', $buscar)
                        ->orWhere('codigo', 'like', $buscar)
                        ->orWhere('marca', 'like', $buscar)
                        ->orWhere('modelo', 'like', $buscar));
                })
                ->when($solicitud->filled('categoria'), fn ($consulta) => $consulta
                    ->where('categoria_id', $solicitud->integer('categoria')))
                ->when($solicitud->input('estado') === 'activos', fn ($consulta) => $consulta->where('activo', true))
                ->when($solicitud->input('estado') === 'inactivos', fn ($consulta) => $consulta->where('activo', false))
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'seriesVendidas' => PiezaInventario::whereNotNull('partida_venta_id')
                ->with('producto', 'partidaVenta.venta.cliente')
                ->latest('creado_en')
                ->limit(50)
                ->get(),
        ]);
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        Producto::create($this->datosValidados($solicitud));

        return to_route('inventario.productos.listado')->with('success', 'El producto fue registrado.');
    }

    public function mostrar(Producto $producto): View
    {
        return view('inventario.productos.detalle', [
            'producto' => $producto->load('categoria', 'seriesVendidas.partidaVenta.venta'),
            'categorias' => Categoria::query()
                ->where(function ($consulta) use ($producto) {
                    $consulta->where('activo', true);

                    if ($producto->categoria_id !== null) {
                        $consulta->orWhere('id', $producto->categoria_id);
                    }
                })
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function actualizar(Request $solicitud, Producto $producto): RedirectResponse
    {
        $producto->update($this->datosValidados($solicitud, $producto));

        return back()->with('success', 'El producto fue actualizado.');
    }

    public function actualizarEstado(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => ! $producto->activo]);

        return back()->with('success', $producto->activo
            ? 'El producto fue reactivado.'
            : 'El producto fue desactivado.');
    }

    private function datosValidados(Request $solicitud, ?Producto $producto = null): array
    {
        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('productos', 'codigo')->ignore($producto)],
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where('activo', true)],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
            'existencias' => ['required', 'integer', 'min:0'],
            'requiere_numero_serie' => ['nullable', 'boolean'],
        ]);
        $datos['requiere_numero_serie'] = $solicitud->boolean('requiere_numero_serie');

        return $datos;
    }
}
