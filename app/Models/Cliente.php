<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo',
        'nombre',
        'rfc',
        'correo',
        'telefono',
        'direccion',
        'codigo_postal',
    ];
}
