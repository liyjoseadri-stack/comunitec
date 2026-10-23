<?php

namespace App\Mail;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoVenta extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Venta $venta) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Comprobante de venta {$this->venta->folio} | COMUN&TEC");
    }

    public function content(): Content
    {
        return new Content(view: 'correos.venta');
    }

    public function attachments(): array
    {
        $this->venta->loadMissing(
            'cotizacion',
            'cliente',
            'responsable',
            'partidas.producto',
            'partidas.servicio',
            'partidas.piezas'
        );

        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('ventas.pdf', ['venta' => $this->venta])->output(),
                "{$this->venta->folio}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
