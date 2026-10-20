<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\GestionArticleController;
use App\Http\Controllers\GestionPromotionController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\StaffCommandeController;
use App\Http\Controllers\StaffMenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'API (toutes préfixées par /api)
|--------------------------------------------------------------------------
| `throttle:xxx`  → limite le nombre de requêtes (anti force brute), voir AppServiceProvider.
| `auth:sanctum`      → exige un jeton valide (en-tête « Authorization: Bearer … »).
| `role:a,b`      → réserve la route à certains rôles (middleware VerifierRole).
*/

// --- Authentification : routes publiques ---------------------------------
Route::prefix('auth')->group(function () {
    Route::post('inscription', [AuthController::class, 'inscription'])->middleware('throttle:inscription');
    Route::post('connexion', [AuthController::class, 'connexion'])->middleware('throttle:connexion');
    Route::get('verification-email/{id}/{hash}', [AuthController::class, 'verifierEmail'])
        ->middleware('signed')->whereNumber('id')->name('api.auth.verifier-email');
    Route::post('renvoyer-verification-email', [AuthController::class, 'renvoyerLienVerification'])->middleware('throttle:mot-de-passe');
    Route::post('mot-de-passe-oublie', [AuthController::class, 'motDePasseOublie'])->middleware('throttle:mot-de-passe');
    Route::post('reinitialiser-mot-de-passe', [AuthController::class, 'reinitialiser'])->middleware('throttle:mot-de-passe');

    // La déconnexion suppose d'être connecté (on supprime le jeton en cours).
    Route::post('deconnexion', [AuthController::class, 'deconnexion'])->middleware(['auth:sanctum', 'compte.actif']);
});

// --- Compte de l'utilisateur connecté ------------------------------------
Route::middleware(['auth:sanctum', 'compte.actif'])->prefix('moi')->group(function () {
    Route::get('/', [ProfilController::class, 'show']);
    Route::patch('/', [ProfilController::class, 'update']);
    Route::put('mot-de-passe', [ProfilController::class, 'changerMotDePasse']);
    Route::delete('/', [ProfilController::class, 'supprimer']);
});

// --- Catalogue : routes publiques (visiteurs non connectés compris) -------
Route::get('categories', [CatalogueController::class, 'categories']);
Route::get('menu-du-jour', [CatalogueController::class, 'menuDuJour']);

// --- Paiements Mobile Money : initiation par l'étudiant, notification signée par l'agrégateur ---
Route::match(['get', 'post'], 'paiements/notification', [PaiementController::class, 'notification'])->middleware('throttle:api');
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->post('commandes/{commande}/paiement', [PaiementController::class, 'initier'])->whereNumber('commande');
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->post('commandes/{commande}/paiement-especes', [PaiementController::class, 'especes'])->whereNumber('commande');
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->post('paiements/{paiement}/simulation', [PaiementController::class, 'simuler'])->whereNumber('paiement');

// --- Réclamations : étudiant → réponse du personnel → décision du gérant ---
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->prefix('reclamations')->group(function () {
    Route::get('/', [ReclamationController::class, 'mesReclamations']);
    Route::post('/', [ReclamationController::class, 'creer']);
});
Route::middleware(['auth:sanctum', 'compte.actif', 'role:employe,gerant,administrateur'])->prefix('staff/reclamations')->group(function () {
    Route::get('/', [ReclamationController::class, 'index']);
    Route::post('{reclamation}/reponse', [ReclamationController::class, 'repondre'])->whereNumber('reclamation');
});
Route::middleware(['auth:sanctum', 'compte.actif', 'role:gerant,administrateur'])->post('gestion/reclamations/{reclamation}/decision', [ReclamationController::class, 'decider'])->whereNumber('reclamation');

// --- Événements et jeux : les points sont calculés par le serveur ---
Route::get('evenements', [EvenementController::class, 'index']);
Route::get('mini-jeux', [EvenementController::class, 'jeux']);
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->group(function () {
    Route::post('evenements/{evenement}/participer', [EvenementController::class, 'participer'])->whereNumber('evenement');
    Route::post('mini-jeux/{jeu}/question', [EvenementController::class, 'question'])->whereNumber('jeu')->middleware('throttle:connexion');
    Route::post('mini-jeux/{jeu}/parties', [EvenementController::class, 'jouer'])->whereNumber('jeu')->middleware('throttle:connexion');
    Route::get('classement', [EvenementController::class, 'classement']);
});

// --- Administration : indicateurs simples et comptes/règles ---
Route::middleware(['auth:sanctum', 'compte.actif', 'role:administrateur'])->prefix('administration')->group(function () {
    Route::get('tableau-de-bord', [AdministrationController::class, 'tableauDeBord']);
    Route::get('equipe', [AdministrationController::class, 'equipe']);
    Route::get('restaurants/{restaurant}/etudiants', [AdministrationController::class, 'etudiantsRestaurant'])->whereNumber('restaurant');
    Route::get('parametres-fidelite', [AdministrationController::class, 'parametres']);
    Route::put('parametres-fidelite', [AdministrationController::class, 'modifierParametres']);
});

// --- Service quotidien : employé, gérant, administrateur ------------------
Route::middleware(['auth:sanctum', 'compte.actif', 'role:employe,gerant,administrateur'])->prefix('staff')->group(function () {
    Route::get('menu', [StaffMenuController::class, 'show']);                                   // articles + présence au menu
    Route::put('menu-du-jour', [StaffMenuController::class, 'composer']);                       // composer le menu du jour
    Route::patch('articles/{article}/disponibilite', [StaffMenuController::class, 'disponibilite']); // rupture / disponible

    // Tableau « Service en cours » : une route par bouton du frontend
    Route::get('commandes', [StaffCommandeController::class, 'index']);
    Route::post('commandes/{commande}/demarrer', [StaffCommandeController::class, 'demarrer']);   // Lancer la préparation
    Route::post('commandes/{commande}/prete', [StaffCommandeController::class, 'prete']);         // Marquer comme PRÊT
    Route::post('commandes/{commande}/cloturer', [StaffCommandeController::class, 'cloturer']);   // Clôturer
    Route::post('commandes/{commande}/notifier', [StaffCommandeController::class, 'notifier']);   // Notifier le client
    Route::post('commandes/{commande}/annuler', [StaffCommandeController::class, 'annuler']);
    Route::post('commandes/{commande}/encaisser-especes', [StaffCommandeController::class, 'encaisserEspeces'])->whereNumber('commande');
});

// --- Commandes de l'étudiant ----------------------------------------------
Route::middleware(['auth:sanctum', 'compte.actif', 'role:etudiant'])->prefix('commandes')->group(function () {
    Route::post('devis', [CommandeController::class, 'devis']);                 // prix exact, sans rien enregistrer
    Route::get('/', [CommandeController::class, 'index']);                      // mes commandes
    Route::post('/', [CommandeController::class, 'store']);                     // passer commande
    Route::get('{id}', [CommandeController::class, 'show'])->whereNumber('id'); // détail + suivi
    Route::post('{id}/confirmer', [CommandeController::class, 'confirmer'])->whereNumber('id'); // test sans paiement
    Route::post('{id}/annuler', [CommandeController::class, 'annuler'])->whereNumber('id');
});

// --- Direction : gérant et administrateur ---------------------------------
Route::middleware(['auth:sanctum', 'compte.actif', 'role:gerant,administrateur'])->prefix('gestion')->group(function () {
    // Catalogue des articles
    Route::get('articles', [GestionArticleController::class, 'index']);
    Route::post('articles', [GestionArticleController::class, 'store']);
    Route::match(['put', 'patch'], 'articles/{article}', [GestionArticleController::class, 'update']);
    // Suppression : administrateur seulement (le gérant n'a que « Modifier détails »).
    // Les DEUX middlewares de rôle s'appliquent : il faut être dans le groupe ET administrateur.
    Route::delete('articles/{article}', [GestionArticleController::class, 'destroy'])->middleware('role:administrateur');

    // Promotions
    Route::get('promotions', [GestionPromotionController::class, 'index']);
    Route::post('promotions', [GestionPromotionController::class, 'store']);
    Route::match(['put', 'patch'], 'promotions/{promotion}', [GestionPromotionController::class, 'update']);
    Route::delete('promotions/{promotion}', [GestionPromotionController::class, 'destroy']);
});
