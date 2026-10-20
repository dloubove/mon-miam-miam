<?php

namespace Tests\Feature\Auth;

use App\Models\Etudiant;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    // Charge les rôles, catégories, paramètres et comptes de démo avant chaque test.
    protected bool $seed = true;

    /** Données d'un formulaire d'inscription valide ; chaque test peut en surcharger certaines. */
    private function formulaire(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Marc Dupont',
            'telephone' => '699 00 00 00',
            'email' => 'marc.dupont@ucac-icam.com',
            'localisation' => 'Campus UCAC-ICAM (Yansoki)',
            'mot_de_passe' => 'Motdepasse1',
        ], $surcharge);
    }

    public function test_un_etudiant_peut_creer_un_compte(): void
    {
        $reponse = $this->postJson('/api/auth/inscription', $this->formulaire());

        // 201 = compte créé. La connexion attend la confirmation de l'adresse email.
        $reponse->assertCreated()
            ->assertJsonPath('email', 'marc.dupont@ucac-icam.com')
            ->assertJsonPath('verification_requise', true)
            ->assertJsonMissingPath('jeton');

        // Le mot de passe ne doit JAMAIS apparaître dans la réponse.
        $this->assertStringNotContainsString('mot_de_passe', $reponse->getContent());

        // Les deux lignes (compte + profil étudiant) existent en base.
        $this->assertDatabaseHas('utilisateur', ['email' => 'marc.dupont@ucac-icam.com']);
        $nouveau = Utilisateur::where('email', 'marc.dupont@ucac-icam.com')->first();
        $this->assertSame('Campus UCAC-ICAM (Yansoki)', Etudiant::find($nouveau->id_utilisateur)->localisation);
        $this->assertNull($nouveau->email_verified_at);
    }

    public function test_le_code_de_parrainage_genere_a_le_bon_format_et_le_mot_de_passe_est_hache(): void
    {
        $this->postJson('/api/auth/inscription', $this->formulaire())->assertCreated();

        $etudiant = Etudiant::latest('id_utilisateur')->first();
        $this->assertMatchesRegularExpression('/^ZEDUC-[A-HJ-NP-Z2-9]{5}$/', $etudiant->code_parrainage);

        $hash = DB::table('utilisateur')->where('email', 'marc.dupont@ucac-icam.com')->value('mot_de_passe');
        $this->assertNotSame('Motdepasse1', $hash);
        $this->assertTrue(password_verify('Motdepasse1', $hash));
    }

    public function test_la_confirmation_email_est_requise_avant_la_connexion(): void
    {
        $this->postJson('/api/auth/inscription', $this->formulaire())->assertCreated();

        $this->postJson('/api/auth/connexion', ['email' => 'marc.dupont@ucac-icam.com', 'mot_de_passe' => 'Motdepasse1'])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_non_verifie');

        Utilisateur::where('email', 'marc.dupont@ucac-icam.com')->update(['email_verified_at' => now()]);
        $jeton = $this->postJson('/api/auth/connexion', ['email' => 'marc.dupont@ucac-icam.com', 'mot_de_passe' => 'Motdepasse1'])
            ->assertOk()->json('jeton');

        $this->withToken($jeton)->getJson('/api/moi')
            ->assertOk()
            ->assertJsonPath('nom', 'Marc Dupont');
    }

    public function test_un_code_parrain_valide_cree_le_lien_de_parrainage(): void
    {
        // Le code du compte de démo (Dan Loubove).
        $reponse = $this->postJson('/api/auth/inscription', $this->formulaire(['code_parrain' => 'zeduc-dan01']));

        $reponse->assertCreated();
        $filleul = Etudiant::latest('id_utilisateur')->first();
        $parrain = $this->compte('etudiant@ucac-icam.com');

        $this->assertSame($parrain->id_utilisateur, $filleul->id_parrain);
        $this->assertNotNull($filleul->date_parrainage);
        // Récompense prévue (10 points) mais PAS encore versée : elle le sera à la 1re commande payée.
        $this->assertSame(10, $filleul->points_parrainage);
        $this->assertFalse($filleul->recompense_attribuee);
        $this->assertSame(45, $parrain->etudiant->fresh()->solde_points);
    }

    public function test_un_code_parrain_inconnu_est_refuse(): void
    {
        $this->postJson('/api/auth/inscription', $this->formulaire(['code_parrain' => 'ZEDUC-XXXXX']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code_parrain');
        $this->assertDatabaseMissing('utilisateur', ['email' => 'marc.dupont@ucac-icam.com']);
    }

    public function test_les_mots_de_passe_faibles_sont_refuses(): void
    {
        foreach (['court1A', 'sansmajuscule1', 'SansChiffreIci'] as $faible) {
            $this->postJson('/api/auth/inscription', $this->formulaire(['mot_de_passe' => $faible]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('mot_de_passe');
        }
    }

    public function test_un_email_deja_inscrit_est_refuse_meme_avec_des_majuscules(): void
    {
        $this->postJson('/api/auth/inscription', $this->formulaire(['email' => 'ETUDIANT@ucac-icam.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_les_champs_obligatoires_sont_verifies_avec_des_messages_en_francais(): void
    {
        $this->postJson('/api/auth/inscription', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nom', 'telephone', 'email', 'localisation', 'mot_de_passe'])
            ->assertJsonPath('errors.nom.0', 'Le champ nom est obligatoire.');
    }

    public function test_on_ne_peut_pas_s_attribuer_un_role_a_l_inscription(): void
    {
        // Un pirate ajoute des champs dans la requête : ils doivent être ignorés.
        $this->postJson('/api/auth/inscription', $this->formulaire(['role' => 'administrateur', 'statut_compte' => 'actif', 'solde_points' => 9999]))
            ->assertCreated()
            ->assertJsonMissing(['role' => 'administrateur']);
        $nouveau = Utilisateur::where('email', 'marc.dupont@ucac-icam.com')->firstOrFail();
        $this->assertSame('etudiant', $nouveau->role);
        $this->assertSame(0, $nouveau->etudiant->solde_points);
    }
}
