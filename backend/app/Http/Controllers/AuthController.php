<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnexionRequest;
use App\Http\Requests\InscriptionRequest;
use App\Http\Requests\ReinitialiserMotDePasseRequest;
use App\Http\Resources\UtilisateurResource;
use App\Models\Utilisateur;
use App\Services\AuthService;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Points d'entrée de l'authentification.
 * Le contrôleur reste fin : il reçoit la requête, appelle le service, formate la réponse.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    /** POST /api/auth/inscription : crée un compte étudiant et demande la vérification de son email. */
    public function inscription(InscriptionRequest $request): JsonResponse
    {
        // validated() ne contient QUE les champs déclarés dans rules() : pas de données parasites.
        $utilisateur = $this->auth->inscrire($request->validated());

        return response()->json([
            'message' => 'Compte créé. Consultez votre email pour confirmer votre adresse avant de vous connecter.',
            'verification_requise' => true,
            'email' => $utilisateur->email,
        ], 201);
    }

    /** GET /api/auth/verification-email/{id}/{hash} : confirme une adresse via un lien signé. */
    public function verifierEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $utilisateur = Utilisateur::findOrFail($id);
        if (! hash_equals(sha1($utilisateur->email), $hash)) {
            abort(403, 'Lien de vérification invalide.');
        }

        if (! $utilisateur->email_verified_at) {
            $utilisateur->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/connexion?email_verifie=1');
    }

    /** POST /api/auth/renvoyer-verification-email : renvoie le lien sans révéler l'existence du compte. */
    public function renvoyerLienVerification(Request $request): JsonResponse
    {
        $donnees = $request->validate(['email' => ['required', 'email']]);
        $this->auth->renvoyerVerification(mb_strtolower(trim($donnees['email'])));

        return response()->json([
            'message' => 'Si un compte non confirmé existe pour cette adresse, un nouveau lien vient de lui être envoyé.',
        ], 202);
    }

    /** POST /api/auth/connexion : échange email + mot de passe contre un jeton. */
    public function connexion(ConnexionRequest $request): JsonResponse
    {
        $utilisateur = $this->auth->connecter($request->input('email'), $request->input('mot_de_passe'));

        return $this->reponseAvecJeton($utilisateur, $request->boolean('se_souvenir'));
    }

    /** POST /api/auth/deconnexion : supprime le jeton utilisé pour cette requête. */
    public function deconnexion(Request $request): Response
    {
        $jeton = $request->user()->currentAccessToken();
        if ($jeton instanceof PersonalAccessToken) {
            $jeton->delete();
        }

        // 204 = « c'est fait, rien à renvoyer ».
        return response()->noContent();
    }

    /** POST /api/auth/mot-de-passe-oublie : envoie le lien par email (si le compte existe). */
    public function motDePasseOublie(Request $request): JsonResponse
    {
        $donnees = $request->validate(['email' => ['required', 'email']]);

        $this->auth->demanderReinitialisation(mb_strtolower(trim($donnees['email'])));

        // Réponse IDENTIQUE que le compte existe ou non : on ne révèle pas qui est inscrit.
        return response()->json(
            ['message' => 'Si un compte existe avec cet email, un lien de réinitialisation vient de lui être envoyé.'],
            202,
        );
    }

    /** POST /api/auth/reinitialiser-mot-de-passe : enregistre le nouveau mot de passe. */
    public function reinitialiser(ReinitialiserMotDePasseRequest $request): JsonResponse
    {
        $this->auth->reinitialiser($request->input('email'), $request->input('jeton'), $request->input('mot_de_passe'));

        return response()->json(['message' => 'Votre mot de passe a été modifié. Vous pouvez vous connecter.']);
    }

    /** Fabrique la réponse commune à l'inscription et à la connexion. */
    private function reponseAvecJeton(Utilisateur $utilisateur, bool $longueDuree, int $statut = 200): JsonResponse
    {
        $nouveauJeton = $utilisateur->createToken(
            'web',
            ['*'],
            now()->addDays($longueDuree ? 30 : 1),
        );

        return response()->json([
            'utilisateur' => new UtilisateurResource($utilisateur),
            'jeton' => $nouveauJeton->plainTextToken, // à stocker côté frontend, envoyé ensuite en « Bearer »
            'type' => 'Bearer',
            'expire_le' => $nouveauJeton->accessToken->expires_at?->toIso8601String(),
        ], $statut);
    }
}
