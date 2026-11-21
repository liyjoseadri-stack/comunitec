<?php

namespace App\Http\Requests;

use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Rules\NumeroFinito;
use App\Support\CalculosCotizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SolicitudGuardarCotizacionCompleta extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'area_solicitante' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:3000'],
            'porcentaje_descuento' => [
                'bail',
                'nullable',
                new NumeroFinito,
                'integer',
                CalculosCotizacion::REGLA_PRECISION,
                function ($atributo, $valor, $fallar) {
                    if ((float) $valor !== 0.0 && ((float) $valor < 5 || (float) $valor > 10)) {
                        $fallar('El descuento general debe ser 0 o estar entre 5% y 10%.');
                    }
                },
            ],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.tipo' => ['required', Rule::in([PartidaCotizacion::TIPO_PRODUCTO, PartidaCotizacion::TIPO_SERVICIO])],
            'items.*.producto_id' => [
                'nullable',
                'required_if:items.*.tipo,'.PartidaCotizacion::TIPO_PRODUCTO,
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'items.*.servicio_id' => [
                'nullable',
                'required_if:items.*.tipo,'.PartidaCotizacion::TIPO_SERVICIO,
                Rule::exists('servicios', 'id')->where('activo', true),
            ],
            'items.*.descripcion' => ['nullable', 'string', 'max:1000'],
            'items.*.cantidad' => [
                'bail',
                'required',
                new NumeroFinito,
                'integer',
                CalculosCotizacion::REGLA_PRECISION,
                'gt:0',
            ],
            'items.*.precio_unitario' => [
                'bail',
                'required',
                new NumeroFinito,
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                'gt:0',
            ],
            'items.*.porcentaje_descuento' => [
                'bail',
                'required',
                new NumeroFinito,
                'integer',
                CalculosCotizacion::REGLA_PRECISION,
                'between:0,100',
            ],
            'pc_armadas' => ['nullable', 'array', 'max:20'],
            'pc_armadas.*.nombre' => ['required', 'string', 'max:255'],
            'pc_armadas.*.descripcion' => ['nullable', 'string', 'max:2000'],
            'pc_armadas.*.cantidad' => ['nullable', 'integer', 'between:1,100'],
            'pc_armadas.*.plantilla_id' => [
                'nullable',
                Rule::exists('pc_armadas', 'id')->where('activo', true),
            ],
            'pc_armadas.*.componentes' => ['required', 'array', 'min:1', 'max:100'],
            'pc_armadas.*.componentes.*.producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'pc_armadas.*.componentes.*.tipo_componente_pc' => [
                'required',
                Rule::in(Producto::tiposComponentesPc()),
            ],
            'pc_armadas.*.componentes.*.cantidad' => ['required', 'integer', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'porcentaje_descuento.integer' => 'El descuento general debe ser un número entero.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.porcentaje_descuento.integer' => 'El descuento del concepto debe ser un número entero.',
            'porcentaje_descuento.decimal' => 'El descuento general admite como máximo dos decimales.',
            'items.*.cantidad.decimal' => 'La cantidad admite como máximo dos decimales.',
            'items.*.precio_unitario.decimal' => 'El precio admite como máximo dos decimales.',
            'items.*.porcentaje_descuento.decimal' => 'El descuento del concepto admite como máximo dos decimales.',
            'items.required' => 'Agrega al menos un producto, servicio o PC armada.',
            'items.min' => 'Agrega al menos un producto, servicio o PC armada.',
            'pc_armadas.*.componentes.*.cantidad.integer' => 'La cantidad del componente debe ser un número entero.',
            'pc_armadas.*.componentes.*.cantidad.gt' => 'La cantidad del componente debe ser mayor que cero.',
            'pc_armadas.*.cantidad.integer' => 'La cantidad de equipos debe ser un nÃºmero entero.',
            'pc_armadas.*.cantidad.between' => 'La cantidad de equipos debe estar entre 1 y 100.',
            'items.*.cantidad.gt' => 'La cantidad debe ser mayor que cero.',
            'items.*.precio_unitario.gt' => 'El precio debe ser mayor que cero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->input('items', [])) && empty($this->input('pc_armadas', []))) {
                $validator->errors()->add('items', 'Agrega al menos un producto, servicio o PC armada.');
            }

            foreach ($this->input('items', []) as $indice => $item) {
                if ($validator->errors()->has("items.{$indice}.cantidad")) {
                    continue;
                }
                $cantidad = (float) ($item['cantidad'] ?? 0);
                if (($item['tipo'] ?? null) === PartidaCotizacion::TIPO_PRODUCTO && floor($cantidad) !== $cantidad) {
                    $validator->errors()->add(
                        "items.{$indice}.cantidad",
                        'Los productos requieren cantidades enteras.'
                    );
                }
            }

            foreach ($this->input('pc_armadas', []) as $indice => $pcArmada) {
                $vistos = [];
                foreach ($pcArmada['componentes'] ?? [] as $indiceComponente => $componente) {
                    $producto = Producto::find($componente['producto_id'] ?? null);
                    if ($producto === null) {
                        continue;
                    }
                    if (($componente['tipo_componente_pc'] ?? null) !== $producto->tipo_componente_pc) {
                        $validator->errors()->add(
                            "pc_armadas.{$indice}.componentes.{$indiceComponente}.tipo_componente_pc",
                            'El apartado no corresponde al tipo de componente del producto.'
                        );
                    }
                    if (in_array($producto->id, $vistos, true)) {
                        $validator->errors()->add(
                            "pc_armadas.{$indice}.componentes.{$indiceComponente}.producto_id",
                            'No repitas el mismo producto dentro de una PC.'
                        );
                    }
                    $vistos[] = $producto->id;
                }
            }
        });
    }
}
