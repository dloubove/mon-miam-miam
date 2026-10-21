<?php

namespace App\Mail;

use App\Models\Utilisateur;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email contenant le lien pour choisir un nouveau mot de passe. */
class ReinitialisationMotDePasseMail extends Mailable
{
    // Les propriétés publiques sont automatiquement disponibles dans la vue de l'email.
    public function __construct(
        public Utilisateur $utilisateur,
        public string $lien,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Réinitialisation de votre mot de passe — Mon Miam Miam');
    }

    public function content(): Content
    {
        // Email en texte brut : simple, lisible partout. Voir resources/views/emails/reinitialisation.blade.php
        return new Content(text: 'emails.reinitialisation');
    }
}
