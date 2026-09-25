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
        'producto_id',
        'servicio_id',
        'tipo',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function getArticuloAttribute(): Producto|Servicio|null
    {
        return $this->producto ?? $this->servicio;
    }
}
