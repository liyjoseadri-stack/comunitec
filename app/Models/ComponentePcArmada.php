<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class ComponentePcArmada extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'componentes_pc_armada';

    protected $fillable = [
        'pc_armada_id',
        'producto_id',
        'tipo_componente_pc',
        'cantidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'orden' => 'integer',
        ];
    }

    public function pcArmada()
    {
        return $this->belongsTo(PcArmada::class, 'pc_armada_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
