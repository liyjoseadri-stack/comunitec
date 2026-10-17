<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PiezaInventario extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const ESTADO_DISPONIBLE = 'disponible';

    public const ESTADO_RESERVADA = 'reservada';

    public const ESTADO_ENTREGADA = 'entregada';

    public static function estados(): array
    {
        return [
            self::ESTADO_DISPONIBLE,
            self::ESTADO_RESERVADA,
            self::ESTADO_ENTREGADA,
        ];
    }

    protected $table = 'piezas_inventario';

    protected $fillable = [
        'producto_id',
        'numero_serie',
        'estado',
        'cotizacion_id',
        'partida_venta_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function articulo()
    {
        return $this->producto();
    }

    public function getArticuloAttribute(): ?Producto
    {
        return $this->producto;
    }

    public function partidaVenta()
    {
        return $this->belongsTo(PartidaVenta::class, 'partida_venta_id');
    }
}
