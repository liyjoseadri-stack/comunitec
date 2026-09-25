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

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'categoria_id');
    }
}
