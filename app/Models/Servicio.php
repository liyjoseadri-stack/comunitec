<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'servicios';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'codigo',
        'unidad',
        'precio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function partidasCotizacion()
    {
        return $this->hasMany(PartidaCotizacion::class, 'servicio_id');
    }

    public function partidasVenta()
    {
        return $this->hasMany(PartidaVenta::class, 'servicio_id');
    }
}
