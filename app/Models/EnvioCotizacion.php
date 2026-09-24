<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioCotizacion extends Model
{
    public const RESULTADO_ACEPTADO = 'accepted';

    public const RESULTADO_FALLIDO = 'failed';

    protected $table = 'quote_email_deliveries';

    protected $fillable = [
        'quote_id',
        'user_id',
        'recipient',
        'result',
        'message',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'quote_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function etiquetaResultado(): string
    {
        return match ($this->result) {
            self::RESULTADO_ACEPTADO => 'Aceptado por el servicio de correo',
            self::RESULTADO_FALLIDO => 'Fallido',
            default => 'Sin definir',
        };
    }
}
