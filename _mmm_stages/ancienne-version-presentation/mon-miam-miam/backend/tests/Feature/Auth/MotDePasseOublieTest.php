<?php

namespace Tests\Feature\Auth;

use App\Mail\ReinitialisationMotDePasseMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

/** Parcours « mot de passe oublié » : demande par email puis choix d'un nouveau mot de passe. */
class MotDePasseOublieTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    /** Demande un lien et renvoie le jeton EN CLAIR lu dans l'email envoyé (comme le ferait l'étudiant). */
    private function demanderLien(string $email = 'etudiant@ucac-icam.com'): string
    {
        $jeton = null;

        $this->postJson('/api/auth/mot-de-passe-oublie', ['email' => $email])->assertStatus(202);

        Mail::assertSent(ReinitialisationMotDePasseMail::class, function ($mail) use (&$jeton) {
            // Le lien contient ?jeton=...&email=... : on en extrait le jeton.
            parse_str(parse_url($mail->lien, PHP_URL_QUERY), $parametres);
            $jeton = $parametres['jeton'];

            return str_starts_with($mail->lien, config('app.frontend_url').'/reinitialisation?');
        });

        return $jeton;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Mail::fake() intercepte les emails : aucun email n'est réellement envoyé pendant les tests.
        Mail::fake();
    }

    public function test_un_email_inconnu_recoit_la_meme_reponse_mais_aucun_email_n_est_envoye(): void
    {
        $this->postJson('/api/auth/mot-de-passe-oublie', ['email' => 'inconnu@ucac-icam.com'])
            ->assertStatus(202)
            ->assertJsonPath('message', 'Si un compte existe avec cet email, un lien de réinitialisation vient de lui être envoyé.');

        Mail::assertNothingSent();
        $this->assertSame(0, DB::table('jeton_mdp')->count());
    }

    public function test_un_email_connu_declenche_l_envoi_du_lien_et_seule_l_empreinte_est_stockee(): void
    {
        $jeton = $this->demanderLien();

        $this->assertDatabaseHas('jeton_mdp', ['jeton_hash' => hash('sha256', $jeton), 'jeton_utilise' => false]);
        $this->assertDatabaseMissing('jeton_mdp', ['jeton_hash' => $jeton]);
    }

    public function test_le_lien_permet_de_choisir_un_nouveau_mot_de_passe(): void
    {
        $jeton = $this->demanderLien();
        // Une connexion existante avant la réinitialisation : elle doit être coupée.
        $ancienJeton = $this->jetonPour('etudiant@ucac-icam.com');

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'etudiant@ucac-icam.com', 'jeton' => $jeton,
            'mot_de_passe' => 'Reinit12345', 'mot_de_passe_confirmation' => 'Reinit12345',
        ])->assertOk();

        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => 'Reinit12345'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($ancienJeton)->getJson('/api/moi')->assertUnauthorized();
    }

    public function test_un_lien_ne_sert_qu_une_fois(): void
    {
        $jeton = $this->demanderLien();
        $donnees = ['email' => 'etudiant@ucac-icam.com', 'jeton' => $jeton, 'mot_de_passe' => 'Reinit12345', 'mot_de_passe_confirmation' => 'Reinit12345'];

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', $donnees)->assertOk();
        $this->postJson('/api/auth/reinitialiser-mot-de-passe', $donnees)
            ->assertUnprocessable()->assertJsonValidationErrors('jeton');
    }

    public function test_un_lien_expire_est_refuse(): void
    {
        $jeton = $this->demanderLien();
        // On simule le passage du temps : le lien expire dans le passé.
        DB::table('jeton_mdp')->update(['date_expiration_jeton' => now()->subMinute()]);

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'etudiant@ucac-icam.com', 'jeton' => $jeton, 'mot_de_passe' => 'Reinit12345', 'mot_de_passe_confirmation' => 'Reinit12345',
        ])->assertUnprocessable()->assertJsonValidationErrors('jeton');
    }

    public function test_une_nouvelle_demande_invalide_l_ancien_lien(): void
    {
        $premier = $this->demanderLien();
        $this->demanderLien();

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'etudiant@ucac-icam.com', 'jeton' => $premier, 'mot_de_passe' => 'Reinit12345', 'mot_de_passe_confirmation' => 'Reinit12345',
        ])->assertUnprocessable();
    }

    public function test_le_lien_d_un_utilisateur_ne_marche_pas_pour_un_autre_compte(): void
    {
        $jeton = $this->demanderLien('etudiant@ucac-icam.com');

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'paul@zeduc.com', 'jeton' => $jeton, 'mot_de_passe' => 'Reinit12345', 'mot_de_passe_confirmation' => 'Reinit12345',
        ])->assertUnprocessable();
        // Le mot de passe de Paul est inchangé.
        $this->postJson('/api/auth/connexion', ['email' => 'paul@zeduc.com', 'mot_de_passe' => $this->motDePasse])->assertOk();
    }

    public function test_le_nouveau_mot_de_passe_doit_etre_solide_et_confirme(): void
    {
        $jeton = $this->demanderLien();

        $this->postJson('/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'etudiant@ucac-icam.com', 'jeton' => $jeton, 'mot_de_passe' => 'faible', 'mot_de_passe_confirmation' => 'faible',
        ])->assertUnprocessable()->assertJsonValidationErrors('mot_de_passe');
    }
}
