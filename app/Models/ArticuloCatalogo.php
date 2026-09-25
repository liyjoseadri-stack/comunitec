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
        'descripcion',
        'codigo',
        'categoria_id',
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
        return $this->hasMany(PiezaInventario::class, 'articulo_catalogo_id');
    }

    public function seriesVendidas()
    {
        return $this->hasMany(PiezaInventario::class, 'articulo_catalogo_id')
            ->whereNotNull('partida_venta_id');
    }

    public function partidasCotizacion()
    {
        return $this->hasMany(PartidaCotizacion::class, 'articulo_catalogo_id');
    }

    public function partidasVenta()
    {
        return $this->hasMany(PartidaVenta::class, 'articulo_catalogo_id');
    }
}
