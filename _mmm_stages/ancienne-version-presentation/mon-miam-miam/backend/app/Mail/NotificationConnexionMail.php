<?php

namespace App\Mail;

use App\Models\Utilisateur;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Avertit l'utilisateur qu'une connexion à son compte vient d'avoir lieu. */
class NotificationConnexionMail extends Mailable
{
    public function __construct(public Utilisateur $utilisateur) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouvelle connexion à votre compte — Mon Miam Miam');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.notification-connexion');
    }
}
