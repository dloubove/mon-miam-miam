<?php

namespace Tests\Feature\Paiements;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GereLesConnexions;
use Tests\Concerns\PrepareLesCommandes;
use Tests\TestCase;

class PaiementMobileMoneyTest extends TestCase
{
    use GereLesConnexions, PrepareLesCommandes, RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mobile_money.mode' => 'simulation']);
    }

    public function test_paiement_simule_confirme_la_commande_et_cree_les_points_une_seule_fois(): void
    {
        $commande = $this->creerCommande('etudiant@ucac-icam.com', ['Poulet Braisé' => 1]);

        $paiement = $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/commandes/{$commande->id_commande}/paiement", [
            'telephone' => '699123456', 'operateur' => 'mtn_momo',
        ])->assertCreated()->assertJsonPath('paiement.statut_paiement', 'en_attente')->json('paiement.id_paiement');

        $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/paiements/{$paiement}/simulation", ['status' => 'success'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/paiements/{$paiement}/simulation", ['status' => 'success'])->assertOk();

        $this->assertDatabaseHas('commande', ['id_commande' => $commande->id_commande, 'statut_commande' => 'en_attente']);
        $this->assertDatabaseHas('paiement', ['id_paiement' => $paiement, 'statut_paiement' => 'reussi']);
        $this->assertDatabaseHas('mouvement_points', ['id_commande' => $commande->id_commande, 'type_mouvement' => 'gain_commande', 'nombre_points' => 2]);
        $this->assertSame(47, $this->solde('etudiant@ucac-icam.com'));
        $this->assertSame(1, DB::table('mouvement_points')->where('id_commande', $commande->id_commande)->where('type_mouvement', 'gain_commande')->count());
    }

    public function test_un_etudiant_ne_peut_pas_simuler_le_paiement_d_un_autre(): void
    {
        $commande = $this->creerCommande('etudiant@ucac-icam.com', ['Poulet Braisé' => 1]);
        $paiement = $this->enTantQue('etudiant@ucac-icam.com')->postJson("/api/commandes/{$commande->id_commande}/paiement", [
            'telephone' => '699123456', 'operateur' => 'orange_money',
        ])->assertCreated()->json('paiement.id_paiement');

        $autre = $this->autreEtudiant();
        $this->app['auth']->forgetGuards();
        $this->enTantQue($autre)->postJson("/api/paiements/{$paiement}/simulation", ['status' => 'success'])->assertNotFound();
    }
}
