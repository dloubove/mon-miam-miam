<?php

namespace App\Mail;

use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email « votre commande est prête » (bouton « Notifier » du tableau du personnel). */
class CommandePreteMail extends Mailable
{
    public function __construct(public Utilisateur $utilisateur, public Commande $commande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre commande #{$this->commande->id_commande} est prête — Mon Miam Miam");
    }

    public function content(): Content
    {
        return new Content(text: 'emails.commande_prete');
    }
}
