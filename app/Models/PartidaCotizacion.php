<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class PartidaCotizacion extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const TIPO_PRODUCTO = 'producto';

    public const TIPO_SERVICIO = 'servicio';

    public const TIPO_OTRO = 'otro';

    public static function tipos(): array
    {
        return [
            self::TIPO_PRODUCTO,
            self::TIPO_SERVICIO,
            self::TIPO_OTRO,
        ];
    }

    protected $table = 'partidas_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'servicio_id',
        'tipo',
        'descripcion',
        'nombre_concepto',
        'codigo_concepto',
        'marca_concepto',
        'modelo_concepto',
        'unidad_concepto',
        'requiere_numero_serie',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'requiere_numero_serie' => 'boolean',
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function getArticuloAttribute(): Producto|Servicio|null
    {
        return $this->producto ?? $this->servicio;
    }

    public function nombreHistorico(): string
    {
        if (filled(trim($this->nombre_concepto ?? ''))) {
            return $this->nombre_concepto;
        }

        foreach ([$this->articulo?->nombre, $this->descripcion] as $nombre) {
            if (filled(trim($nombre ?? ''))) {
                return $nombre;
            }
        }

        return 'Concepto histórico';
    }

    public function codigoHistorico(): ?string
    {
        if (filled(trim($this->nombre_concepto ?? ''))) {
            return $this->codigo_concepto;
        }

        return $this->codigo_concepto ?? $this->articulo?->codigo;
    }
}
