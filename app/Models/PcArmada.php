<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PcArmada extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'pc_armadas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function componentes()
    {
        return $this->hasMany(ComponentePcArmada::class, 'pc_armada_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function configuracionesCotizacion()
    {
        return $this->hasMany(ConfiguracionPcCotizacion::class, 'pc_armada_id');
    }
}
