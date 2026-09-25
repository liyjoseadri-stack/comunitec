<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const METODO_EFECTIVO = 'efectivo';

    public const METODO_TRANSFERENCIA = 'transferencia';

    public const METODO_TARJETA = 'tarjeta';

    public const METODO_OTRO = 'otro';

    protected $table = 'ventas';

    protected $fillable = [
        'folio',
        'cotizacion_id',
        'cliente_id',
        'tipo_cliente',
        'nombre_cliente',
        'rfc_cliente',
        'correo_cliente',
        'telefono_cliente',
        'direccion_cliente',
        'codigo_postal_cliente',
        'usuario_id',
        'nombre_responsable',
        'correo_responsable',
        'vendida_en',
        'metodo_pago',
        'detalle_metodo_pago',
        'subtotal',
        'porcentaje_descuento',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'vendida_en' => 'datetime',
            'subtotal' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function partidas()
    {
        return $this->hasMany(PartidaVenta::class, 'venta_id');
    }

    public function etiquetaMetodoPago(): string
    {
        return match ($this->metodo_pago) {
            self::METODO_EFECTIVO => 'Efectivo',
            self::METODO_TRANSFERENCIA => 'Transferencia',
            self::METODO_TARJETA => 'Tarjeta',
            self::METODO_OTRO => $this->detalle_metodo_pago
                ? 'Otro: '.$this->detalle_metodo_pago
                : 'Otro',
            default => 'Sin definir',
        };
    }

    public static function metodosPago(): array
    {
        return [
            self::METODO_EFECTIVO,
            self::METODO_TRANSFERENCIA,
            self::METODO_TARJETA,
            self::METODO_OTRO,
        ];
    }

    public function nombreClienteMostrado(): string
    {
        return trim((string) $this->nombre_cliente) !== ''
            ? $this->nombre_cliente
            : ($this->cliente?->nombre ?? 'Cliente no disponible');
    }

    public function nombreResponsableMostrado(): string
    {
        return trim((string) $this->nombre_responsable) !== ''
            ? $this->nombre_responsable
            : ($this->responsable?->nombre ?? 'Responsable no disponible');
    }
}
