<?php

namespace App\Mail;

use App\Models\Utilisateur;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationEmailMail extends Mailable
{
    public function __construct(public Utilisateur $utilisateur, public string $lien) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmez votre adresse email — Mon Miam Miam');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.verification-adresse');
    }
}
