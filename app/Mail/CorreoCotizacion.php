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

    public function __construct(public Cotizacion $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cotización {$this->quote->folio} | COMUN&TEC");
    }

    public function content(): Content
    {
        return new Content(view: 'correos.cotizacion');
    }

    public function attachments(): array
    {
        return [

            Attachment::fromData(
                fn (): string => Pdf::loadView('cotizaciones.pdf',
                    [
                        'quote' => $this->quote,
                    ])->output(),

                "{$this->quote->folio}.pdf"
            )->withMime('application/pdf'),

        ];
    }
}
