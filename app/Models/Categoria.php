<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'activo',
    ];
}
