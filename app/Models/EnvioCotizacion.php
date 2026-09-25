<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class EnvioCotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const RESULTADO_ACEPTADO = 'aceptada';

    public const RESULTADO_FALLIDO = 'fallido';

    protected $table = 'envios_correo_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'usuario_id',
        'destinatario',
        'resultado',
        'mensaje',
        'intentado_en',
    ];

    protected function casts(): array
    {
        return [
            'intentado_en' => 'datetime',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function etiquetaResultado(): string
    {
        return match ($this->resultado) {
            self::RESULTADO_ACEPTADO => 'Aceptado por el servicio de correo',
            self::RESULTADO_FALLIDO => 'Fallido',
            default => 'Sin definir',
        };
    }
}
