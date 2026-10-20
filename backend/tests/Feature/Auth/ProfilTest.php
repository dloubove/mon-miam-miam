<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

/** Gestion du compte par son propriétaire : modifier, changer de mot de passe, supprimer. */
class ProfilTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_un_etudiant_modifie_ses_informations_et_son_lieu_de_livraison(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')->patchJson('/api/moi', [
            'nom' => 'Dan L. Loubove',
            'telephone' => '677 11 22 33',
            'localisation' => 'Résidence La Terrasse',
        ])->assertOk()
            ->assertJsonPath('nom', 'Dan L. Loubove')
            ->assertJsonPath('etudiant.localisation', 'Résidence La Terrasse');

        $this->assertDatabaseHas('utilisateur', ['email' => 'etudiant@ucac-icam.com', 'telephone' => '677 11 22 33']);
    }

    public function test_une_modification_partielle_ne_touche_pas_aux_autres_champs(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')->patchJson('/api/moi', ['nom' => 'Nouveau Nom'])->assertOk();

        $this->assertDatabaseHas('utilisateur', ['email' => 'etudiant@ucac-icam.com', 'nom' => 'Nouveau Nom', 'telephone' => '699 12 34 56']);
    }

    public function test_on_ne_peut_pas_prendre_l_email_d_un_autre_mais_on_peut_garder_le_sien(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')
            ->patchJson('/api/moi', ['email' => 'paul@zeduc.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        // Renvoyer son propre email (cas d'un formulaire envoyé en entier) reste valide.
        $this->enTantQue('etudiant@ucac-icam.com')
            ->patchJson('/api/moi', ['email' => 'etudiant@ucac-icam.com', 'nom' => 'Dan'])->assertOk();
    }

    public function test_on_ne_peut_pas_modifier_ses_points_ni_son_role_par_le_profil(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')->patchJson('/api/moi', [
            'solde_points' => 99999, 'statut_compte' => 'actif', 'role' => 'administrateur', 'nom' => 'Dan',
        ])->assertOk()->assertJsonPath('etudiant.solde_points', 45)->assertJsonPath('role', 'etudiant');
    }

    public function test_un_employe_peut_modifier_son_profil_sans_lieu_de_livraison(): void
    {
        $this->enTantQue('paul@zeduc.com')->patchJson('/api/moi', ['telephone' => '670 00 00 00', 'localisation' => 'ignoré'])
            ->assertOk()->assertJsonPath('telephone', '670 00 00 00');
    }

    public function test_changement_de_mot_de_passe_deconnecte_les_autres_appareils(): void
    {
        $telephone = $this->jetonPour('etudiant@ucac-icam.com');
        $ordinateur = $this->jetonPour('etudiant@ucac-icam.com');

        $this->withToken($ordinateur)->putJson('/api/moi/mot-de-passe', [
            'mot_de_passe_actuel' => $this->motDePasse,
            'nouveau_mot_de_passe' => 'NouveauMdp1',
            'nouveau_mot_de_passe_confirmation' => 'NouveauMdp1',
        ])->assertOk();

        // Le nouveau mot de passe fonctionne, l'ancien non.
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => 'NouveauMdp1'])->assertOk();
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])->assertUnprocessable();

        // Le téléphone (autre appareil) est déconnecté, l'ordinateur (celui qui a changé le mot de passe) reste connecté.
        $this->app['auth']->forgetGuards();
        $this->withToken($telephone)->getJson('/api/moi')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($ordinateur)->getJson('/api/moi')->assertOk();
    }

    public function test_le_changement_de_mot_de_passe_exige_l_ancien_et_un_mot_de_passe_solide(): void
    {
        $nouveau = ['nouveau_mot_de_passe' => 'NouveauMdp1', 'nouveau_mot_de_passe_confirmation' => 'NouveauMdp1'];

        // Ancien mot de passe faux
        $this->enTantQue('etudiant@ucac-icam.com')->putJson('/api/moi/mot-de-passe', ['mot_de_passe_actuel' => 'Faux12345'] + $nouveau)
            ->assertUnprocessable()->assertJsonValidationErrors('mot_de_passe_actuel');

        // Confirmation différente
        $this->enTantQue('etudiant@ucac-icam.com')->putJson('/api/moi/mot-de-passe', [
            'mot_de_passe_actuel' => $this->motDePasse, 'nouveau_mot_de_passe' => 'NouveauMdp1', 'nouveau_mot_de_passe_confirmation' => 'Autre1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('nouveau_mot_de_passe');

        // Trop faible
        $this->enTantQue('etudiant@ucac-icam.com')->putJson('/api/moi/mot-de-passe', [
            'mot_de_passe_actuel' => $this->motDePasse, 'nouveau_mot_de_passe' => 'faible', 'nouveau_mot_de_passe_confirmation' => 'faible',
        ])->assertUnprocessable()->assertJsonValidationErrors('nouveau_mot_de_passe');

        // Identique à l'ancien
        $this->enTantQue('etudiant@ucac-icam.com')->putJson('/api/moi/mot-de-passe', [
            'mot_de_passe_actuel' => $this->motDePasse, 'nouveau_mot_de_passe' => $this->motDePasse, 'nouveau_mot_de_passe_confirmation' => $this->motDePasse,
        ])->assertUnprocessable()->assertJsonValidationErrors('nouveau_mot_de_passe');
    }

    public function test_la_suppression_du_compte_anonymise_les_donnees_et_coupe_les_connexions(): void
    {
        $id = $this->compte('etudiant@ucac-icam.com')->id_utilisateur;
        $jeton = $this->jetonPour('etudiant@ucac-icam.com');

        $this->withToken($jeton)->deleteJson('/api/moi', ['mot_de_passe' => $this->motDePasse])->assertNoContent();

        // La ligne existe encore (l'historique des commandes en dépend)… mais sans données personnelles.
        $this->assertDatabaseHas('utilisateur', [
            'id_utilisateur' => $id, 'nom' => 'Compte supprimé', 'email' => "supprime-{$id}@invalide.local", 'statut_compte' => 'inactif',
        ]);
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $id)->count());

        // Plus de connexion possible, ni avec l'ancien email, ni avec l'ancien jeton.
        $this->postJson('/api/auth/connexion', ['email' => 'etudiant@ucac-icam.com', 'mot_de_passe' => $this->motDePasse])->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->withToken($jeton)->getJson('/api/moi')->assertUnauthorized();
    }

    public function test_la_suppression_du_compte_exige_le_mot_de_passe(): void
    {
        $this->enTantQue('etudiant@ucac-icam.com')->deleteJson('/api/moi', ['mot_de_passe' => 'Faux12345'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('utilisateur', ['email' => 'etudiant@ucac-icam.com', 'statut_compte' => 'actif']);
    }
}
