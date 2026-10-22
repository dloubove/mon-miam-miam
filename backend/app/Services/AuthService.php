<?php

namespace App\Services;

use App\Mail\ReinitialisationMotDePasseMail;
use App\Mail\NotificationConnexionMail;
use App\Models\Etudiant;
use App\Models\Utilisateur;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier de l'authentification : inscription, connexion,
 * oubli et réinitialisation du mot de passe.
 */
class AuthService
{
    /** Hash « leurre » utilisé quand l'email n'existe pas (voir connecter()). */
    private static ?string $hashLeurre = null;

    public function __construct(private readonly ParametreService $parametres) {}

    /**
     * Inscription d'un étudiant.
     *
     * @param  array  $donnees  données déjà validées par InscriptionRequest
     */
    public function inscrire(array $donnees): Utilisateur
    {
        // Transaction : si une étape échoue, rien n'est enregistré (pas de compte « à moitié » créé).
        $utilisateur = DB::transaction(function () use ($donnees) {
            // 1) Le compte de connexion. Le mot de passe est haché automatiquement (cast « hashed »).
            $utilisateur = Utilisateur::create([
                'nom' => $donnees['nom'],
                'email' => $donnees['email'],
                'telephone' => $donnees['telephone'],
                'mot_de_passe' => $donnees['mot_de_passe'],
            ]);

            // 2) Le parrain éventuel, retrouvé grâce à son code (déjà vérifié par la validation).
            $parrain = ! empty($donnees['code_parrain'])
                ? Etudiant::where('code_parrainage', $donnees['code_parrain'])->first()
                : null;

            // 3) Le profil étudiant. Le solde de points démarre à 0.
            Etudiant::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'code_parrainage' => $this->genererCodeParrainage(),
                'solde_points' => 0,
                'localisation' => $donnees['localisation'],
                // Si l'étudiant a un parrain, on enregistre le lien et la récompense prévue.
                // La récompense sera versée plus tard, à la 1re commande payée (étape 5).
                'id_parrain' => $parrain?->id_utilisateur,
                'date_parrainage' => $parrain ? now() : null,
                'points_parrainage' => $parrain ? $this->parametres->entier('points_parrainage', 10) : null,
            ]);

            return $utilisateur;
        });

        return $utilisateur;
    }

    /**
     * Vérifie email + mot de passe et renvoie l'utilisateur.
     *
     * @throws ValidationException si les identifiants sont faux (message volontairement vague)
     * @throws HttpResponseException si le compte est désactivé
     */
    public function connecter(string $email, string $motDePasse): Utilisateur
    {
        $utilisateur = Utilisateur::where('email', $email)->first();

        // Sécurité : on exécute TOUJOURS une vérification de hash, même si l'email n'existe pas.
        // Sans cela, une réponse « instantanée » révélerait qu'aucun compte n'existe avec cet email.
        self::$hashLeurre ??= Hash::make(Str::random(32));
        $hashAVerifier = $utilisateur?->mot_de_passe ?? self::$hashLeurre;
        $motDePasseValide = Hash::check($motDePasse, $hashAVerifier);

        if (! $utilisateur || ! $motDePasseValide) {
            // Même message dans les deux cas : on ne dit pas si c'est l'email ou le mot de passe.
            throw ValidationException::withMessages(['email' => ['Email ou mot de passe incorrect.']]);
        }

        if (! $utilisateur->estActif()) {
            throw new HttpResponseException(response()->json(['message' => 'Ce compte est désactivé.'], 403));
        }

        // Si l'algorithme de hachage a évolué depuis la création du compte, on met le hash à jour.
        if (Hash::needsRehash($utilisateur->mot_de_passe)) {
            $utilisateur->update(['mot_de_passe' => $motDePasse]);
        }

        // Le mail informe le propriétaire du compte, mais ne conditionne jamais l'accès.
        try {
            Mail::to($utilisateur->email)->send(new NotificationConnexionMail($utilisateur));
        } catch (\Throwable $erreur) {
            report($erreur);
        }

        return $utilisateur;
    }

    /**
     * « Mot de passe oublié » : envoie un lien de réinitialisation par email.
     * Ne révèle jamais si l'email existe (le contrôleur répond pareil dans tous les cas).
     */
    public function demanderReinitialisation(string $email): void
    {
        $utilisateur = Utilisateur::where('email', $email)->first();
        if (! $utilisateur || ! $utilisateur->estActif()) {
            return; // on s'arrête en silence
        }

        // Les anciens liens non utilisés deviennent inutilisables : un seul lien valide à la fois.
        DB::table('jeton_mdp')
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('jeton_utilise', false)
            ->update(['jeton_utilise' => true]);

        // Jeton aléatoire : le clair part par email, seule l'empreinte est stockée.
        $clair = Str::random(64);
        DB::table('jeton_mdp')->insert([
            'jeton_hash' => hash('sha256', $clair),
            'date_expiration_jeton' => now()->addMinutes(60),
            'id_utilisateur' => $utilisateur->id_utilisateur,
        ]);

        // Lien vers la page du frontend qui affichera le formulaire « nouveau mot de passe ».
        $lien = rtrim(config('app.frontend_url'), '/').'/reinitialisation?'.http_build_query([
            'jeton' => $clair,
            'email' => $utilisateur->email,
        ]);

        Mail::to($utilisateur->email)->send(new ReinitialisationMotDePasseMail($utilisateur, $lien));
    }

    /**
     * Applique le nouveau mot de passe si le lien est valide (existant, non utilisé, non expiré).
     *
     * @throws ValidationException si le lien est invalide ou expiré
     */
    public function reinitialiser(string $email, string $jetonClair, string $nouveauMotDePasse): void
    {
        $utilisateur = Utilisateur::where('email', $email)->first();

        // On cherche une ligne qui correspond à CET utilisateur, CE jeton, encore valable.
        $ligne = $utilisateur ? DB::table('jeton_mdp')
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('jeton_hash', hash('sha256', $jetonClair))
            ->where('jeton_utilise', false)
            ->where('date_expiration_jeton', '>', now())
            ->first() : null;

        if (! $ligne) {
            throw ValidationException::withMessages(['jeton' => ['Ce lien est invalide ou a expiré. Refaites une demande.']]);
        }

        DB::transaction(function () use ($utilisateur, $ligne, $nouveauMotDePasse) {
            $utilisateur->update(['mot_de_passe' => $nouveauMotDePasse]);
            // Le lien ne peut servir qu'une fois.
            DB::table('jeton_mdp')->where('id_jeton', $ligne->id_jeton)->update(['jeton_utilise' => true]);
            // Par sécurité, on déconnecte tous les appareils (quelqu'un a peut-être volé l'ancien mot de passe).
            $utilisateur->tokens()->delete();
        });
    }

    /**
     * Génère un code de parrainage unique, de la forme ZEDUC-A98X2.
     * L'alphabet exclut les caractères qu'on confond (0/O, 1/I) pour faciliter la saisie.
     */
    private function genererCodeParrainage(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffixe = '';
            for ($i = 0; $i < 5; $i++) {
                $suffixe .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'ZEDUC-'.$suffixe;
            // On recommence dans le cas (très rare) où ce code existe déjà.
        } while (Etudiant::where('code_parrainage', $code)->exists());

        return $code;
    }
}
