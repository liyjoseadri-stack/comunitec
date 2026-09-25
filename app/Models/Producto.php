<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'codigo',
        'marca',
        'modelo',
        'unidad',
        'precio',
        'existencias',
        'requiere_numero_serie',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'requiere_numero_serie' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function piezasInventario()
    {
        return $this->hasMany(PiezaInventario::class, 'producto_id');
    }

    public function seriesVendidas()
    {
        return $this->piezasInventario()->whereNotNull('partida_venta_id');
    }

    public function partidasCotizacion()
    {
        return $this->hasMany(PartidaCotizacion::class, 'producto_id');
    }

    public function partidasVenta()
    {
        return $this->hasMany(PartidaVenta::class, 'producto_id');
    }
}
