<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'quotes';

    protected $fillable = [
        'folio',
        'customer_id',
        'user_id',
        'area_requesting',
        'status',
        'sent_at',
        'accepted_at',
        'expires_at',
        'discount_percent',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'discount_percent' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function lines()
    {
        return $this->hasMany(PartidaCotizacion::class, 'quote_id');
    }

    public function customer()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function etiquetaEstado(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada',
            'rejected' => 'Rechazada',
            'cancelled' => 'Cancelada',
            'expired' => 'Vencida',
            default => 'Sin definir',
        };
    }
}
