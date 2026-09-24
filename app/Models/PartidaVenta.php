<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartidaVenta extends Model
{
    protected $table = 'sale_lines';

    protected $fillable = [
        'sale_id',
        'quote_line_id',
        'catalog_item_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'sale_id');
    }

    public function partidaCotizada()
    {
        return $this->belongsTo(PartidaCotizacion::class, 'quote_line_id');
    }

    public function piezas()
    {
        return $this->hasMany(PiezaInventario::class, 'sale_line_id');
    }
}
