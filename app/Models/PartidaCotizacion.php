<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PartidaCotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'partidas_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'articulo_catalogo_id',
        'tipo',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function articulo()
    {
        return $this->belongsTo(ArticuloCatalogo::class, 'articulo_catalogo_id');
    }
}
