<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'folio',
        'cliente_id',
        'usuario_id',
        'area_solicitante',
        'estado',
        'enviada_en',
        'aceptada_en',
        'vence_en',
        'porcentaje_descuento',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'enviada_en' => 'datetime',
            'aceptada_en' => 'datetime',
            'vence_en' => 'datetime',
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

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
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
        ];
    }
}
