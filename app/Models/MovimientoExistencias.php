<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoExistencias extends Model
{
    protected $table = 'movimientos_existencias';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'existencias_anteriores',
        'existencias_nuevas',
        'motivo',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'existencias_anteriores' => 'integer',
            'existencias_nuevas' => 'integer',
            'creado_en' => 'datetime',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
