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

    public function articulos()
    {
        return $this->hasMany(ArticuloCatalogo::class, 'categoria_id');
    }
}
