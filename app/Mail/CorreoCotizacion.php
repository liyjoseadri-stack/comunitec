<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoCotizacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Cotizacion $cotizacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cotización {$this->cotizacion->folio} | COMUN&TEC");
    }

    public function content(): Content
    {
        return new Content(view: 'correos.cotizacion');
    }

    public function attachments(): array
    {
        $this->cotizacion->loadMissing('cliente', 'partidas.articulo', 'responsable');

        return [

            Attachment::fromData(
                fn (): string => Pdf::loadView('cotizaciones.pdf',
                    [
                        'cotizacion' => $this->cotizacion,
                    ])->output(),

                "{$this->cotizacion->folio}.pdf"
            )->withMime('application/pdf'),

        ];
    }
}
