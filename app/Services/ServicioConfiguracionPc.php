<?php

namespace App\Services;

use App\Models\ConfiguracionPcCotizacion;
use App\Models\Cotizacion;
use App\Models\PcArmada;
use App\Models\Producto;
use App\Support\CalculosCotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioConfiguracionPc
{
    public function crearPlantilla(array $datos): PcArmada
    {
        return DB::transaction(function () use ($datos): PcArmada {
            $componentes = $this->validarComponentes($datos['componentes'] ?? []);
            $plantilla = PcArmada::create([
                'nombre' => trim($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?? null,
                'activo' => true,
            ]);

            $this->guardarComponentesPlantilla($plantilla, $componentes);

            return $plantilla->load('componentes.producto');
        });
    }

    public function actualizarPlantilla(PcArmada $plantilla, array $datos): PcArmada
    {
        return DB::transaction(function () use ($plantilla, $datos): PcArmada {
            $plantilla = PcArmada::query()->lockForUpdate()->findOrFail($plantilla->id);
            $componentes = $this->validarComponentes($datos['componentes'] ?? []);
            $plantilla->update([
                'nombre' => trim($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?? null,
            ]);
            $plantilla->componentes()->delete();
            $this->guardarComponentesPlantilla($plantilla, $componentes);

            return $plantilla->load('componentes.producto');
        });
    }

    public function crearConfiguracionEnCotizacion(
        Cotizacion $cotizacion,
        array $datos,
        ?PcArmada $plantilla = null
    ): ConfiguracionPcCotizacion {
        return DB::transaction(function () use ($cotizacion, $datos, $plantilla): ConfiguracionPcCotizacion {
            $cantidadPc = (int) ($datos['cantidad'] ?? 1);
            if ($cantidadPc < 1 || $cantidadPc > 100) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad de equipos debe estar entre 1 y 100.',
                ]);
            }
            $componentes = $plantilla
                ? $plantilla->load('componentes.producto')->componentes->map(fn ($componente): array => [
                    'producto_id' => $componente->producto_id,
                    'tipo_componente_pc' => $componente->tipo_componente_pc,
                    'cantidad' => $componente->cantidad,
                    'porcentaje_descuento' => 0,
                ])->all()
                : ($datos['componentes'] ?? []);
            $componentes = $this->validarComponentes($componentes, $cantidadPc);
            $configuracion = $cotizacion->configuracionesPc()->create([
                'pc_armada_id' => $plantilla?->id,
                'nombre' => trim((string) ($plantilla?->nombre ?? $datos['nombre'] ?? 'PC personalizada')),
                'descripcion' => trim((string) ($plantilla?->descripcion ?? $datos['descripcion'] ?? '')) ?: null,
                'cantidad' => $cantidadPc,
            ]);

            foreach ($componentes as $componente) {
                $producto = $componente['producto'];
                $descuento = (float) ($componente['porcentaje_descuento'] ?? 0);
                $cantidad = (int) $componente['cantidad'] * $cantidadPc;
                $precio = (float) $producto->precio;

                $cotizacion->partidas()->create([
                    'producto_id' => $producto->id,
                    'tipo' => 'producto',
                    'configuracion_pc_cotizacion_id' => $configuracion->id,
                    'tipo_componente_pc' => $producto->tipo_componente_pc,
                    'descripcion' => trim((string) $producto->descripcion) ?: $producto->nombre,
                    'nombre_concepto' => $producto->nombre,
                    'codigo_concepto' => $producto->codigo,
                    'marca_concepto' => $producto->marca,
                    'modelo_concepto' => $producto->modelo,
                    'unidad_concepto' => $producto->unidad,
                    'requiere_numero_serie' => $producto->requiere_numero_serie,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'porcentaje_descuento' => $descuento,
                    'subtotal' => CalculosCotizacion::descontar($cantidad * $precio, $descuento),
                ]);
            }

            $cotizacion->update([
                'total' => CalculosCotizacion::descontar(
                    (float) $cotizacion->partidas()->sum('subtotal'),
                    (float) $cotizacion->porcentaje_descuento
                ),
            ]);

            return $configuracion->load('partidas.producto');
        });
    }

    private function validarComponentes(array $componentes, int $multiplicador = 1): array
    {
        if ($componentes === []) {
            throw ValidationException::withMessages([
                'componentes' => 'Agrega al menos un componente a la PC.',
            ]);
        }

        $resultado = [];
        $productosUsados = [];
        foreach ($componentes as $indice => $componente) {
            $producto = Producto::query()->find($componente['producto_id'] ?? null);
            $tipo = $componente['tipo_componente_pc'] ?? null;
            $cantidad = (int) ($componente['cantidad'] ?? 0);

            if ($producto === null) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.producto_id" => 'El producto seleccionado no existe.',
                ]);
            }
            if (! $producto->activo) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.producto_id" => 'El producto está inactivo y no puede agregarse a una PC nueva.',
                ]);
            }
            if (! in_array($tipo, Producto::tiposComponentesPc(), true) || $producto->tipo_componente_pc !== $tipo) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.tipo_componente_pc" => 'El producto no corresponde al apartado seleccionado.',
                ]);
            }
            if ($cantidad < 1) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.cantidad" => 'La cantidad debe ser un entero mayor que cero.',
                ]);
            }
            if ($cantidad * $multiplicador > (int) $producto->existencias) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.cantidad" => 'La cantidad supera las existencias disponibles del producto.',
                ]);
            }
            if (isset($productosUsados[$producto->id])) {
                throw ValidationException::withMessages([
                    "componentes.{$indice}.producto_id" => 'No repitas el mismo producto dentro de la PC.',
                ]);
            }

            $productosUsados[$producto->id] = true;
            $resultado[] = [
                ...$componente,
                'producto' => $producto,
                'cantidad' => $cantidad,
            ];
        }

        return $resultado;
    }

    private function guardarComponentesPlantilla(PcArmada $plantilla, array $componentes): void
    {
        foreach ($componentes as $orden => $componente) {
            $plantilla->componentes()->create([
                'producto_id' => $componente['producto']->id,
                'tipo_componente_pc' => $componente['producto']->tipo_componente_pc,
                'cantidad' => $componente['cantidad'],
                'orden' => $orden,
            ]);
        }
    }
}
