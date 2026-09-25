<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_ACEPTADA = 'aceptada';

    public const ESTADO_RECHAZADA = 'rechazada';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADO_VENCIDA = 'vencida';

    public const ESTADO_VENTA = 'venta';

    protected $table = 'cotizaciones';

    protected $fillable = [
        'folio',
        'cliente_id',
        'usuario_id',
        'area_solicitante',
        'notas',
        'estado',
        'enviada_en',
        'aceptada_en',
        'vence_en',
        'entrega_limite_en',
        'porcentaje_descuento',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'enviada_en' => 'datetime',
            'aceptada_en' => 'datetime',
            'vence_en' => 'datetime',
            'entrega_limite_en' => 'datetime',
            'porcentaje_descuento' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function partidas()
    {
        return $this->hasMany(PartidaCotizacion::class, 'cotizacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function enviosCorreo()
    {
        return $this->hasMany(EnvioCotizacion::class, 'cotizacion_id')->latest('intentado_en');
    }

    public function venta()
    {
        return $this->hasOne(Venta::class, 'cotizacion_id');
    }

    public function fechaLimiteEntrega()
    {
        return $this->entrega_limite_en ?? $this->vence_en;
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
            self::ESTADO_VENTA => 'Venta',
            default => 'Sin definir',
        };
    }

    public static function estados(): array
    {
        return [
            'borrador',
            'pendiente',
            'aceptada',
            'rechazada',
            'cancelada',
            'vencida',
            self::ESTADO_VENTA,
        ];
    }
}
