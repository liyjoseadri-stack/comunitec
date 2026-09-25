<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PartidaVenta extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'partidas_venta';

    protected $fillable = [
        'venta_id',
        'partida_cotizacion_id',
        'producto_id',
        'servicio_id',
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

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function partidaCotizada()
    {
        return $this->belongsTo(PartidaCotizacion::class, 'partida_cotizacion_id');
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

    public function piezas()
    {
        return $this->hasMany(PiezaInventario::class, 'partida_venta_id');
    }
}
