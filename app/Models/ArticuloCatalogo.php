<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticuloCatalogo extends Model
{
    protected $table = 'catalog_items';

    protected $fillable = [
        'type',
        'name',
        'code',
        'category_id',
        'brand',
        'model',
        'unit',
        'price',
        'stock',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function inventoryUnits()
    {
        return $this->hasMany(PiezaInventario::class, 'catalog_item_id');
    }
}
