<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartidaCotizacion extends Model
{
    protected $table = 'quote_lines';

    protected $fillable = [
        'quote_id',
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

    public function quote()
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
