<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationConnexionMail;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

class ConnexionTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_chaque_role_peut_se_connecter_et_recoit_son_role(): void
    {
        // email du compte de démo => rôle attendu
        $comptes = [
            'etudiant@ucac-icam.com' => 'etudiant',
            'paul@zeduc.com' => 'employe',
            'emma@zeduc.com' => 'gerant',
            'admin@zeduc.com' => 'administrateur',
        ];

        foreach ($comptes as $email => $role) {
            $this->postJson('/api/auth/connexion', ['email' => $email, 'mot_de_passe' => $this->motDePasse])
                ->assertOk()
                ->assertJsonPath('utilisateur.role', $role)
                ->assertJsonStructure(['jeton', 'type', 'expire_le']);
        }
    }

    public function test_le_personnel_recoit_son_matricule_mais_pas_de_bloc_etudiant(): void
    {
        $this->postJson('/api/auth/connexion', ['email' => 'paul@zeduc.com', 'mot_de_passe' => $this->motDePasse])
            ->assertOk()
            ->assertJsonPath('utilisateur.personnel.matricule', 'EMP-102')
            ->assertJsonMissingPath('utilisateur.etudiant');
    }

    public function test_une_connexion_reussie_envoie_un_email_de_notification(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])
            ->assertOk();

        Mail::assertSent(NotificationConnexionMail::class, fn ($mail) => $mail->hasTo('etudiant@ucac-icam.com'));
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => 'Mauvais123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email ou mot de passe incorrect.');
    }

    public function test_un_email_inconnu_donne_exactement_le_meme_message(): void
    {
        // Sinon un pirate pourrait découvrir quels emails sont inscrits.
        $this->postJson('/api/auth/connexion', ['email' => 'inconnu@ucac-icam.com', 'mot_de_passe' => 'Quelconque1'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email ou mot de passe incorrect.');
    }

    public function test_l_email_est_insensible_a_la_casse(): void
    {
        $this->postJson('/api/auth/connexion', ['email' => '  Etudiant@UCAC-icam.com ', 'mot_de_passe' => $this->motDePasse])
            ->assertOk();
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $this->compte('etudiant@ucac-icam.com')->update(['statut_compte' => 'inactif']);

        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])
            ->assertForbidden()
            ->assertJsonPath('message', 'Ce compte est désactivé.');
    }

    public function test_apres_5_echecs_les_tentatives_sont_bloquees(): void
    {
        // 5 tentatives ratées par minute sont tolérées…
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => 'Faux12345'])
                ->assertUnprocessable();
        }

        // …la 6e est bloquée (429), même avec le BON mot de passe : c'est le principe anti force brute.
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Trop de tentatives. Réessayez dans quelques instants.');
    }

    public function test_se_souvenir_de_moi_allonge_la_duree_de_la_connexion(): void
    {
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])->assertOk();
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse, 'se_souvenir' => true])->assertOk();

        [$courte, $longue] = PersonalAccessToken::orderBy('id')->get()->all();

        $this->assertTrue($courte->expires_at->lt(now()->addDays(2)), 'Connexion normale : ~1 jour');
        $this->assertTrue($longue->expires_at->gt(now()->addDays(29)), 'Se souvenir de moi : ~30 jours');
    }

    public function test_seule_l_empreinte_du_jeton_est_stockee(): void
    {
        $clair = $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])->json('jeton');

        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $clair]);
        // Sanctum renvoie « id|secret » mais ne stocke que le hash du secret.
        $secret = explode('|', $clair, 2)[1];
        $this->assertDatabaseHas('personal_access_tokens', ['token' => hash('sha256', $secret)]);
    }
}
