<?php

namespace Tests\Concerns;

use App\Models\Article;
use App\Models\Commande;
use App\Services\CommandeService;

/**
 * Outils partagés par les tests de commandes. Le DemoSeeder fournit les articles, le menu du jour et l'étudiant
 * etudiant@ucac-icam.com (45 points). Les commandes de démonstration, elles, ne sont PAS créées en test.
 */
trait PrepareLesCommandes
{
    /** Active le mode « confirmation sans paiement » (utilisé tant que l'étape 5 n'existe pas). */
    protected function autoriserConfirmationDirecte(bool $actif = true): void
    {
        config(['commandes.confirmation_directe' => $actif]);
    }

    /** Données d'une commande à partir de « nom d'article => quantité ». */
    protected function donneesCommande(array $articles, string $mode = 'sur_place', array $extra = []): array
    {
        return array_merge([
            'lignes' => collect($articles)->map(fn ($quantite, $nom) => [
                'article_id' => Article::where('nom_article', $nom)->value('id_article'),
                'quantite' => $quantite,
            ])->values()->all(),
            'mode_service' => $mode,
            'heure_arrivee' => $mode === 'sur_place' ? '12:30' : null,
            'adresse_livraison' => $mode === 'livraison' ? 'Résidence Les Mangueries' : null,
        ], $extra);
    }

    /** Crée une commande « panier » directement par le service (sans passer par HTTP). */
    protected function creerCommande(string $emailEtudiant, array $articles, string $mode = 'sur_place', array $extra = []): Commande
    {
        return app(CommandeService::class)->creer($this->compte($emailEtudiant), $this->donneesCommande($articles, $mode, $extra));
    }

    /** Crée puis confirme une commande : elle est alors « en_attente » et visible du personnel. */
    protected function commandeConfirmee(string $emailEtudiant, array $articles, string $mode = 'sur_place', array $extra = []): Commande
    {
        $commande = $this->creerCommande($emailEtudiant, $articles, $mode, $extra);

        return app(CommandeService::class)->confirmer($commande, $this->compte($emailEtudiant));
    }

    /** Un second étudiant, pour vérifier qu'on ne voit pas les commandes des autres. */
    protected function autreEtudiant(): string
    {
        $email = 'autre@ucac-icam.com';
        $id = \DB::table('utilisateur')->insertGetId([
            'nom' => 'Autre Étudiant', 'email' => $email, 'telephone' => '677 00 00 00', 'mot_de_passe' => bcrypt($this->motDePasse),
        ], 'id_utilisateur');
        \DB::table('etudiant')->insert(['id_utilisateur' => $id, 'code_parrainage' => 'ZEDUC-AUTR3', 'solde_points' => 0, 'localisation' => 'Campus']);

        return $email;
    }

    /** Solde de points actuel d'un étudiant. */
    protected function solde(string $email): int
    {
        return (int) \DB::table('etudiant')->where('id_utilisateur', $this->compte($email)->id_utilisateur)->value('solde_points');
    }
}
