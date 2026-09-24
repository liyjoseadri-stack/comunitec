<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    public const METODO_EFECTIVO = 'cash';

    public const METODO_TRANSFERENCIA = 'transfer';

    public const METODO_TARJETA = 'card';

    public const METODO_OTRO = 'other';

    protected $table = 'sales';

    protected $fillable = [
        'folio',
        'quote_id',
        'customer_id',
        'customer_type',
        'customer_name',
        'customer_rfc',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_postal_code',
        'user_id',
        'responsible_name',
        'responsible_email',
        'sold_at',
        'payment_method',
        'payment_method_detail',
        'subtotal',
        'discount_percent',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'quote_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'customer_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function partidas()
    {
        return $this->hasMany(PartidaVenta::class, 'sale_id');
    }

    public function etiquetaMetodoPago(): string
    {
        return match ($this->payment_method) {
            self::METODO_EFECTIVO => 'Efectivo',
            self::METODO_TRANSFERENCIA => 'Transferencia',
            self::METODO_TARJETA => 'Tarjeta',
            self::METODO_OTRO => $this->payment_method_detail
                ? 'Otro: '.$this->payment_method_detail
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
        return trim((string) $this->customer_name) !== ''
            ? $this->customer_name
            : ($this->cliente?->name ?? 'Cliente no disponible');
    }

    public function nombreResponsableMostrado(): string
    {
        return trim((string) $this->responsible_name) !== ''
            ? $this->responsible_name
            : ($this->responsable?->name ?? 'Responsable no disponible');
    }
}
