<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PiezaInventario extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'piezas_inventario';

    protected $fillable = [
        'articulo_catalogo_id',
        'numero_serie',
        'estado',
        'cotizacion_id',
        'partida_venta_id',
    ];

    public function articulo()
    {
        return $this->belongsTo(ArticuloCatalogo::class, 'articulo_catalogo_id');
    }

    public function partidaVenta()
    {
        return $this->belongsTo(PartidaVenta::class, 'partida_venta_id');
    }
}
