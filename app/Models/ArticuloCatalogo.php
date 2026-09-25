<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class ArticuloCatalogo extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'articulos_catalogo';

    protected $fillable = [
        'tipo',
        'nombre',
        'codigo',
        'categoria_id',
        'marca',
        'modelo',
        'unidad',
        'precio',
        'existencias',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function piezasInventario()
    {
        return $this->hasMany(PiezaInventario::class, 'articulo_catalogo_id');
    }
}
