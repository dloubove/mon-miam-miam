Bonjour {{ $utilisateur->nom }},

Votre commande #{{ $commande->id_commande }} est prête.
@if ($commande->mode_service === 'livraison')
Elle va vous être livrée à l'adresse indiquée : {{ $commande->adresse_livraison }}.
@else
Vous pouvez venir la récupérer sur la terrasse.
@endif

Bon appétit !
L'équipe ZeDuc@Space
