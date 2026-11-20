<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionPcCotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'configuraciones_pc_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'pc_armada_id',
        'nombre',
        'descripcion',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(PcArmada::class, 'pc_armada_id');
    }

    public function partidas()
    {
        return $this->hasMany(PartidaCotizacion::class, 'configuracion_pc_cotizacion_id');
    }
}
