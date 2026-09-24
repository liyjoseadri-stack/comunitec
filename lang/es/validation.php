<?php

return [

    'required' => 'El campo :attribute es obligatorio.',

    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',

    'required_unless' => 'El campo :attribute es obligatorio salvo que :other sea :values.',

    'email' => 'El campo :attribute debe ser un correo válido.',

    'string' => 'El campo :attribute debe ser texto.',

    'numeric' => 'El campo :attribute debe ser un número.',

    'integer' => 'El campo :attribute debe ser un número entero.',

    'boolean' => 'El campo :attribute debe ser verdadero o falso.',

    'unique' => 'El valor de :attribute ya está registrado.',

    'exists' => 'El valor seleccionado de :attribute no es válido.',

    'in' => 'El valor seleccionado de :attribute no es válido.',

    'confirmed' => 'La confirmación de :attribute no coincide.',

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],

    'max' => [
        'numeric' => 'El campo :attribute no debe superar :max.',
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],

    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
    ],

    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
    ],

    'attributes' => [

        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'role' => 'rol',
        'active' => 'activo',

        'type' => 'tipo',
        'rfc' => 'RFC',
        'phone' => 'teléfono',
        'address' => 'dirección',
        'postal_code' => 'código postal',

        'customer_id' => 'cliente',
        'catalog_item_id' => 'artículo de catálogo',
        'category_id' => 'categoría',

        'description' => 'descripción',
        'quantity' => 'cantidad',
        'unit_price' => 'precio unitario',
        'price' => 'precio',

        'discount_percent' => 'descuento',
        'area_requesting' => 'área solicitante',
        'serial_number' => 'número de serie',

        'code' => 'código',
        'brand' => 'marca',
        'model' => 'modelo',
        'unit' => 'unidad',
        'stock' => 'existencias',

    ],

];
