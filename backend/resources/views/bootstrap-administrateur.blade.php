<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer le premier administrateur — Mon Miam Miam</title>
    <style>
        body { margin: 0; background: #f5f3ee; color: #211f1b; font: 16px/1.5 system-ui, sans-serif; }
        main { box-sizing: border-box; max-width: 540px; margin: 7vh auto; padding: 32px; background: white; border: 1px solid #e5e0d6; border-radius: 16px; box-shadow: 0 12px 40px #211f1b12; }
        h1 { margin-top: 0; font-size: 1.7rem; } p { color: #57534e; }
        label { display: block; margin-top: 16px; font-weight: 650; }
        input { box-sizing: border-box; width: 100%; margin-top: 6px; padding: 11px 12px; border: 1px solid #c9c2b4; border-radius: 8px; font: inherit; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 8px; background: #cfbd97; color: #17140f; font: inherit; font-weight: 700; cursor: pointer; }
        .notice { padding: 13px 15px; border-radius: 8px; background: #f5f3ee; }
        .error { color: #a32020; font-size: .92rem; }
        @media (max-width: 600px) { main { margin: 20px 12px; padding: 24px; } }
    </style>
</head>
<body>
<main>
    <h1>Premier administrateur</h1>
    @if ($cree)
        <p class="notice">Le compte administrateur a été créé. Tu peux maintenant te connecter au site.</p>
        <p>Par sécurité, retire maintenant <code>BOOTSTRAP_ADMIN_TOKEN</code> des variables Render et redéploie le service.</p>
    @elseif ($ferme)
        <p class="notice">Un administrateur existe déjà. Cette page de création est désactivée.</p>
        <p>Retire <code>BOOTSTRAP_ADMIN_TOKEN</code> des variables Render.</p>
    @else
        <p>Cette page sert à créer le premier compte administrateur du restaurant.</p>
        @if ($errors->any())
            <div class="error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="post" action="{{ url('/initialiser-administrateur') }}">
            @csrf
            <label for="jeton">Code temporaire</label>
            <input id="jeton" name="jeton" type="password" required autocomplete="off">
            <label for="nom">Nom</label>
            <input id="nom" name="nom" value="{{ old('nom') }}" required minlength="2" maxlength="150" autocomplete="name">
            <label for="email">Adresse email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
            <label for="telephone">Téléphone</label>
            <input id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required autocomplete="tel">
            <label for="mot_de_passe">Mot de passe</label>
            <input id="mot_de_passe" name="mot_de_passe" type="password" required autocomplete="new-password">
            <label for="mot_de_passe_confirmation">Confirmer le mot de passe</label>
            <input id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" type="password" required autocomplete="new-password">
            <button type="submit">Créer l’administrateur</button>
        </form>
        <p>Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.</p>
    @endif
</main>
</body>
</html>
