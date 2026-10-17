<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NumeroFinito implements ValidationRule
{
    public function validate(string $atributo, mixed $valor, Closure $fallar): void
    {
        if (! is_numeric($valor) || ! is_finite((float) $valor)) {
            $fallar('El campo :attribute debe ser un número finito.');
        }
    }
}
