<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

/** Protection des routes : jeton obligatoire, expiration, déconnexion, restriction par rôle. */
class AccesTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_sans_jeton_l_acces_est_refuse_en_json(): void
    {
        $this->getJson('/api/moi')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Authentification requise : connectez-vous pour continuer.');
    }

    public function test_sans_jeton_et_sans_en_tete_accept_la_reponse_reste_un_401_json(): void
    {
        // Cas d'un client qui n'envoie pas « Accept: application/json » (curl, certains navigateurs) :
        // il ne doit pas recevoir une erreur 500 ni une redirection HTML.
        $this->get('/api/moi')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_un_jeton_invente_est_refuse(): void
    {
        $this->withToken(str_repeat('a', 64))->getJson('/api/moi')->assertUnauthorized();
    }

    public function test_un_jeton_expire_est_refuse(): void
    {
        $jeton = $this->jetonPour('etudiant@ucac-icam.com');
        // On vieillit artificiellement le jeton : sa date d'expiration est dans le passé.
        PersonalAccessToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->withToken($jeton)->getJson('/api/moi')->assertUnauthorized();
    }

    public function test_la_deconnexion_invalide_le_jeton(): void
    {
        $jeton = $this->jetonPour('etudiant@ucac-icam.com');

        $this->withToken($jeton)->postJson('/api/auth/deconnexion')->assertNoContent();

        // Le même jeton ne marche plus. (On vide le cache de l'utilisateur de la requête précédente.)
        $this->app['auth']->forgetGuards();
        $this->withToken($jeton)->getJson('/api/moi')->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_la_deconnexion_ne_touche_pas_aux_autres_appareils(): void
    {
        $telephone = $this->jetonPour('etudiant@ucac-icam.com');
        $ordinateur = $this->jetonPour('etudiant@ucac-icam.com');

        $this->withToken($telephone)->postJson('/api/auth/deconnexion')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($ordinateur)->getJson('/api/moi')->assertOk();
    }

    public function test_un_compte_desactive_perd_immediatement_son_acces(): void
    {
        $jeton = $this->jetonPour('etudiant@ucac-icam.com');
        $this->compte('etudiant@ucac-icam.com')->update(['statut_compte' => 'inactif']);

        $this->withToken($jeton)->getJson('/api/moi')->assertUnauthorized();
    }

    public function test_le_profil_renvoye_est_celui_du_proprietaire_du_jeton(): void
    {
        $this->enTantQue('emma@zeduc.com')->getJson('/api/moi')
            ->assertOk()
            ->assertJsonPath('email', 'emma@zeduc.com')
            ->assertJsonPath('role', 'gerant')
            ->assertJsonMissing(['mot_de_passe']);
    }

    public function test_le_middleware_de_role_autorise_ou_refuse_selon_le_role(): void
    {
        // Route de test uniquement : réservée au gérant et à l'administrateur.
        Route::middleware(['api', 'auth:sanctum', 'role:gerant,administrateur'])
            ->get('/api/_test/direction', fn () => response()->json(['ok' => true]));

        $this->enTantQue('emma@zeduc.com')->getJson('/api/_test/direction')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('admin@zeduc.com')->getJson('/api/_test/direction')->assertOk();

        // Employé et étudiant : 403 (identifiés, mais pas autorisés).
        $this->app['auth']->forgetGuards();
        $this->enTantQue('paul@zeduc.com')->getJson('/api/_test/direction')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->getJson('/api/_test/direction')
            ->assertForbidden()
            ->assertJsonPath('message', "Vous n'avez pas les droits pour cette action.");

        // Sans jeton : 401 (on ne sait même pas qui c'est).
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/_test/direction')->assertUnauthorized();
    }

    public function test_une_url_inconnue_de_l_api_renvoie_un_404_json(): void
    {
        $this->getJson('/api/n-importe-quoi')->assertNotFound()->assertJsonPath('message', 'Ressource introuvable.');
    }
}
