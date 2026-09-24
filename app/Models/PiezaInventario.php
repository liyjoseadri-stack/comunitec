<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiezaInventario extends Model
{
    protected $table = 'inventory_units';

    protected $fillable = [
        'catalog_item_id',
        'serial_number',
        'status',
        'quote_id',
    ];

    public function item()
    {
        return $this->belongsTo(ArticuloCatalogo::class, 'catalog_item_id');
    }
}
