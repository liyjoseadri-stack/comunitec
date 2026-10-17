<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\MovimientoExistencias;
use App\Models\PiezaInventario;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        $producto = Producto::create($this->datosValidados($solicitud));

        return to_route('inventario.productos.detalle', $producto)->with('success', 'El producto fue registrado.');
    }

    public function crear(): View
    {
        return view('inventario.productos.crear', [
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function mostrar(Producto $producto): View
    {
        return view('inventario.productos.detalle', [
            'producto' => $producto->load([
                'categoria',
                'seriesVendidas.partidaVenta.venta',
                'movimientosExistencias' => fn ($consulta) => $consulta
                    ->with('responsable')
                    ->latest('creado_en'),
            ]),
        ]);
    }

    public function editar(Producto $producto): View
    {
        return view('inventario.productos.editar', [
            'producto' => $producto->load('categoria'),
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
        $datos = $this->datosValidados($solicitud, $producto);

        DB::transaction(function () use ($solicitud, $producto, $datos): void {
            $productoBloqueado = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $existenciasAnteriores = $productoBloqueado->existencias;
            $existenciasNuevas = (int) $datos['existencias'];
            $cambiaExistencias = $existenciasAnteriores !== $existenciasNuevas;

            $motivo = Validator::make($solicitud->only('motivo_existencias'), [
                'motivo_existencias' => [Rule::requiredIf($cambiaExistencias), 'nullable', 'string', 'max:500'],
            ], [
                'motivo_existencias.required' => 'Explica el motivo del ajuste de existencias.',
                'motivo_existencias.max' => 'El motivo no puede exceder 500 caracteres.',
            ])->validate()['motivo_existencias'] ?? null;

            $productoBloqueado->update($datos);

            if ($cambiaExistencias) {
                MovimientoExistencias::create([
                    'producto_id' => $productoBloqueado->id,
                    'usuario_id' => $solicitud->user()->id,
                    'existencias_anteriores' => $existenciasAnteriores,
                    'existencias_nuevas' => $existenciasNuevas,
                    'motivo' => trim($motivo),
                    'creado_en' => now(),
                ]);
            }
        });

        return to_route('inventario.productos.detalle', $producto)->with('success', 'El producto fue actualizado.');
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
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where(function ($consulta) use ($producto) {
                $consulta->where('activo', true);

                if ($producto?->categoria_id !== null) {
                    $consulta->orWhere('id', $producto->categoria_id);
                }
            })],
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
