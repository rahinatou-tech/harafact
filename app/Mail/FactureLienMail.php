<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FactureLienMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $lienPublic;

    public function __construct(
        public Facture $facture,
        public string $email
    ) {
        $this->lienPublic = route('facture.public', $this->facture->token_public);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->email,
            subject: "Votre facture {$this->facture->numero_facture} — HaRaFact",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.facture-lien',
            with: [
                'facture'    => $this->facture,
                'lienPublic' => $this->lienPublic,
            ],
        );
    }
}
