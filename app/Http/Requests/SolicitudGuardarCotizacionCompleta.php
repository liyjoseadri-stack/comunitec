<?php

namespace App\Http\Requests;

use App\Models\PartidaCotizacion;
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
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                function ($atributo, $valor, $fallar) {
                    if ((float) $valor !== 0.0 && ((float) $valor < 5 || (float) $valor > 10)) {
                        $fallar('El descuento general debe ser 0 o estar entre 5% y 10%.');
                    }
                },
            ],
            'items' => ['required', 'array', 'min:1', 'max:100'],
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
            'items.*.descripcion' => ['required', 'string', 'max:1000'],
            'items.*.cantidad' => [
                'bail',
                'required',
                new NumeroFinito,
                'numeric',
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
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                'between:0,100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'porcentaje_descuento.decimal' => 'El descuento general admite como máximo dos decimales.',
            'items.*.cantidad.decimal' => 'La cantidad admite como máximo dos decimales.',
            'items.*.precio_unitario.decimal' => 'El precio admite como máximo dos decimales.',
            'items.*.porcentaje_descuento.decimal' => 'El descuento del concepto admite como máximo dos decimales.',
            'items.required' => 'Agrega al menos un producto o servicio.',
            'items.min' => 'Agrega al menos un producto o servicio.',
            'items.*.cantidad.gt' => 'La cantidad debe ser mayor que cero.',
            'items.*.precio_unitario.gt' => 'El precio debe ser mayor que cero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
        });
    }
}
