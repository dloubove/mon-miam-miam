<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Contrat OpenAPI centralisé des routes REST de Mon Miam Miam.
 * Les méthodes ne sont pas des contrôleurs : elles servent uniquement au scanner Swagger.
 */
#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'Mon Miam Miam API',
        version: '1.0.0',
        description: 'API du frontend React/Inertia : comptes, menus, commandes, paiements Mobile Money, fidélité et administration.',
    ),
    servers: [new OA\Server(url: '/api', description: 'API de cette installation')],
    security: [],
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Personal Access Token Sanctum',
    description: 'Utiliser le champ `jeton` obtenu après connexion, sans préfixer par « Bearer » ici.',
)]
#[OA\Tag(name: 'Authentification', description: 'Inscription, connexion et gestion des identifiants')]
#[OA\Tag(name: 'Profil', description: 'Compte et profil de la personne connectée')]
#[OA\Tag(name: 'Catalogue', description: 'Catégories et menu public')]
#[OA\Tag(name: 'Commandes', description: 'Devis, création, consultation et suivi des commandes étudiant')]
#[OA\Tag(name: 'Paiements', description: 'Paiements CinetPay Mobile Money, espèces, callbacks et simulation locale')]
#[OA\Tag(name: 'Réclamations', description: 'Création, traitement et décision des réclamations')]
#[OA\Tag(name: 'Fidélité et jeux', description: 'Événements, jeux, participations et classement')]
#[OA\Tag(name: 'Administration', description: 'Indicateurs, équipe, étudiants et paramètres de fidélité')]
#[OA\Tag(name: 'Personnel', description: 'Menu du jour et commandes à préparer')]
#[OA\Tag(name: 'Gestion', description: 'Gestion des articles et promotions')]
final class ApiSpecification
{
    #[OA\Post(path: '/auth/inscription', tags: ['Authentification'], summary: 'Créer un compte étudiant', description: 'Crée le compte puis envoie un lien de confirmation valable 24 heures. Aucun jeton Sanctum n’est remis avant confirmation.', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', example: ['nom' => 'Dan Loubove', 'email' => 'dan@example.cm', 'telephone' => '699000000', 'mot_de_passe' => 'Secret1234', 'localisation' => 'Campus'])) , responses: [new OA\Response(response: 201, description: 'Compte créé ; confirmation email requise'), new OA\Response(response: 422, description: 'Données invalides')])]
    public function inscription(): void {}

    #[OA\Get(path: '/auth/verification-email/{id}/{hash}', tags: ['Authentification'], summary: 'Confirmer une adresse email', description: 'Lien signé et temporaire envoyé après inscription. Après confirmation, redirige vers le frontend.', parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'hash', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 302, description: 'Adresse confirmée, redirection vers la connexion'), new OA\Response(response: 403, description: 'Lien invalide ou expiré')])]
    public function verifierEmail(): void {}

    #[OA\Post(path: '/auth/renvoyer-verification-email', tags: ['Authentification'], summary: 'Renvoyer le lien de confirmation', description: 'Répond sans révéler si le compte existe ou est déjà confirmé.', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', example: ['email' => 'dan@example.cm'])), responses: [new OA\Response(response: 202, description: 'Demande traitée'), new OA\Response(response: 422, description: 'Adresse email invalide')])]
    public function renvoyerVerificationEmail(): void {}

    #[OA\Post(path: '/auth/connexion', tags: ['Authentification'], summary: 'Ouvrir une session API', description: 'Retourne utilisateur, jeton Bearer Sanctum et date d’expiration (1 jour, ou 30 jours si se_souvenir=true). L’adresse doit avoir été confirmée après inscription.', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object', example: ['email' => 'dan@example.cm', 'mot_de_passe' => 'Secret1234', 'se_souvenir' => false])), responses: [new OA\Response(response: 200, description: 'Connexion réussie'), new OA\Response(response: 403, description: 'Adresse email non confirmée'), new OA\Response(response: 422, description: 'Identifiants incorrects')])]
    public function connexion(): void {}

    #[OA\Post(path: '/auth/mot-de-passe-oublie', tags: ['Authentification'], summary: 'Demander un lien de réinitialisation', responses: [new OA\Response(response: 202, description: 'Demande traitée sans révéler si le compte existe'), new OA\Response(response: 422, description: 'Adresse email invalide')])]
    public function motDePasseOublie(): void {}

    #[OA\Post(path: '/auth/reinitialiser-mot-de-passe', tags: ['Authentification'], summary: 'Réinitialiser le mot de passe', responses: [new OA\Response(response: 200, description: 'Mot de passe modifié'), new OA\Response(response: 422, description: 'Jeton de réinitialisation expiré ou invalide')])]
    public function reinitialiser(): void {}

    #[OA\Post(path: '/auth/deconnexion', tags: ['Authentification'], summary: 'Révoquer le jeton courant', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 204, description: 'Jeton courant supprimé'), new OA\Response(response: 401, description: 'Authentification requise')])]
    public function deconnexion(): void {}

    #[OA\Get(path: '/moi', tags: ['Profil'], summary: 'Afficher mon profil', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Profil et rôle du compte'), new OA\Response(response: 401, description: 'Authentification requise')])]
    public function profil(): void {}

    #[OA\Patch(path: '/moi', tags: ['Profil'], summary: 'Modifier mon profil', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Profil modifié'), new OA\Response(response: 422, description: 'Données invalides')])]
    public function modifierProfil(): void {}

    #[OA\Put(path: '/moi/mot-de-passe', tags: ['Profil'], summary: 'Changer mon mot de passe', description: 'Révoque les autres jetons Sanctum ; le jeton courant reste actif.', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Mot de passe modifié'), new OA\Response(response: 422, description: 'Ancien mot de passe incorrect ou mot de passe faible')])]
    public function changerMotDePasse(): void {}

    #[OA\Delete(path: '/moi', tags: ['Profil'], summary: 'Supprimer mon compte', description: 'Anonymise le compte et révoque tous ses jetons.', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 204, description: 'Compte anonymisé')])]
    public function supprimerCompte(): void {}

    #[OA\Get(path: '/categories', tags: ['Catalogue'], summary: 'Lister les catégories publiques', responses: [new OA\Response(response: 200, description: 'Catégories disponibles')])]
    public function categories(): void {}

    #[OA\Get(path: '/menu-du-jour', tags: ['Catalogue'], summary: 'Afficher le menu du jour', responses: [new OA\Response(response: 200, description: 'Articles et disponibilité du jour')])]
    public function menuPublic(): void {}

    #[OA\Post(path: '/commandes/devis', tags: ['Commandes'], summary: 'Calculer un devis sans créer de commande', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Total calculé côté serveur'), new OA\Response(response: 422, description: 'Article ou quantité invalide')])]
    public function devis(): void {}

    #[OA\Get(path: '/commandes', tags: ['Commandes'], summary: 'Lister mes commandes', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Historique des commandes du compte')])]
    public function mesCommandes(): void {}

    #[OA\Post(path: '/commandes', tags: ['Commandes'], summary: 'Créer une commande', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Commande enregistrée'), new OA\Response(response: 422, description: 'Commande invalide')])]
    public function creerCommande(): void {}

    #[OA\Get(path: '/commandes/{id}', tags: ['Commandes'], summary: 'Consulter une commande', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail et état de la commande'), new OA\Response(response: 404, description: 'Commande introuvable')])]
    public function detailCommande(): void {}

    #[OA\Post(path: '/commandes/{id}/confirmer', tags: ['Commandes'], summary: 'Confirmer une commande (mode de test)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Commande confirmée')])]
    public function confirmerCommande(): void {}

    #[OA\Post(path: '/commandes/{id}/annuler', tags: ['Commandes'], summary: 'Annuler une commande', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Commande annulée')])]
    public function annulerCommande(): void {}

    #[OA\Post(path: '/commandes/{commande}/paiement', tags: ['Paiements'], summary: 'Initier un paiement Mobile Money', description: 'En mode cinetpay, crée une session Checkout v2 et retourne url_paiement pour rediriger le navigateur. En mode simulation, le callback local confirme le résultat.', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Paiement initié'), new OA\Response(response: 422, description: 'Commande non payable')])]
    public function initierPaiement(): void {}

    #[OA\Post(path: '/commandes/{commande}/paiement-especes', tags: ['Paiements'], summary: 'Choisir le paiement en espèces', description: 'Confirme la commande pour le personnel mais conserve le paiement en attente jusqu’à l’encaissement physique.', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Commande confirmée, espèces à encaisser'), new OA\Response(response: 409, description: 'Commande déjà traitée')])]
    public function payerEnEspeces(): void {}

    #[OA\Post(path: '/paiements/notification', tags: ['Paiements'], summary: 'Recevoir le callback de paiement', description: 'Pour CinetPay, vérifie le HMAC x-token puis interroge l’API serveur-à-serveur de vérification avant de modifier le paiement.', responses: [new OA\Response(response: 200, description: 'Notification acceptée'), new OA\Response(response: 401, description: 'Signature invalide')])]
    public function callbackPaiement(): void {}

    #[OA\Get(path: '/paiements/notification', tags: ['Paiements'], summary: 'Sonde de disponibilité du callback CinetPay', description: 'Répond 200 lorsque CinetPay vérifie que l’URL de notification est joignable.', responses: [new OA\Response(response: 200, description: 'URL disponible')])]
    public function verifierDisponibiliteCallbackPaiement(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/encaisser-especes', tags: ['Personnel'], summary: 'Confirmer la réception du paiement en espèces', description: 'Réservé au personnel du restaurant associé. Crédite les points après encaissement.', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Encaissement confirmé'), new OA\Response(response: 409, description: 'Paiement déjà traité ou commande annulée')])]
    public function encaisserEspeces(): void {}

    #[OA\Post(path: '/paiements/{paiement}/simulation', tags: ['Paiements'], summary: 'Simuler le résultat d’un paiement en environnement de développement', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'paiement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Simulation appliquée')])]
    public function simulerPaiement(): void {}

    #[OA\Get(path: '/reclamations', tags: ['Réclamations'], summary: 'Lister mes réclamations', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Réclamations de l’étudiant connecté')])]
    public function mesReclamations(): void {}

    #[OA\Post(path: '/reclamations', tags: ['Réclamations'], summary: 'Créer une réclamation', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Réclamation enregistrée')])]
    public function creerReclamation(): void {}

    #[OA\Get(path: '/staff/reclamations', tags: ['Réclamations'], summary: 'Lister les réclamations à traiter', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste pour le personnel autorisé')])]
    public function reclamationsEquipe(): void {}

    #[OA\Post(path: '/staff/reclamations/{reclamation}/reponse', tags: ['Réclamations'], summary: 'Répondre à une réclamation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'reclamation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Réponse enregistrée')])]
    public function repondreReclamation(): void {}

    #[OA\Post(path: '/gestion/reclamations/{reclamation}/decision', tags: ['Réclamations'], summary: 'Rendre la décision finale sur une réclamation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'reclamation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Décision enregistrée')])]
    public function deciderReclamation(): void {}

    #[OA\Get(path: '/evenements', tags: ['Fidélité et jeux'], summary: 'Lister les événements publics', responses: [new OA\Response(response: 200, description: 'Événements disponibles')])]
    public function evenements(): void {}

    #[OA\Get(path: '/mini-jeux', tags: ['Fidélité et jeux'], summary: 'Lister les mini-jeux', responses: [new OA\Response(response: 200, description: 'Jeux disponibles')])]
    public function miniJeux(): void {}

    #[OA\Post(path: '/evenements/{evenement}/participer', tags: ['Fidélité et jeux'], summary: 'Participer à un événement', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'evenement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Participation créée')])]
    public function participerEvenement(): void {}

    #[OA\Post(path: '/mini-jeux/{jeu}/question', tags: ['Fidélité et jeux'], summary: 'Obtenir une question de jeu', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'jeu', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Question du jeu')])]
    public function questionJeu(): void {}

    #[OA\Post(path: '/mini-jeux/{jeu}/parties', tags: ['Fidélité et jeux'], summary: 'Enregistrer une partie et calculer les points côté serveur', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'jeu', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Résultat de la partie')])]
    public function jouer(): void {}

    #[OA\Get(path: '/classement', tags: ['Fidélité et jeux'], summary: 'Afficher le classement des étudiants', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Classement calculé')])]
    public function classement(): void {}

    #[OA\Get(path: '/administration/tableau-de-bord', tags: ['Administration'], summary: 'Afficher les indicateurs administrateur', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Indicateurs globaux')])]
    public function tableauDeBord(): void {}

    #[OA\Get(path: '/administration/equipe', tags: ['Administration'], summary: 'Lister l’équipe', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Comptes du personnel')])]
    public function equipe(): void {}

    #[OA\Get(path: '/administration/restaurants/{restaurant}/etudiants', tags: ['Administration'], summary: 'Lister les étudiants ayant un compte dans un restaurant', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'restaurant', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Étudiants associés à ce restaurant')])]
    public function etudiantsRestaurant(): void {}

    #[OA\Get(path: '/administration/parametres-fidelite', tags: ['Administration'], summary: 'Consulter les paramètres de fidélité', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Règles et barèmes de points')])]
    public function parametresFidelite(): void {}

    #[OA\Put(path: '/administration/parametres-fidelite', tags: ['Administration'], summary: 'Modifier les paramètres de fidélité', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paramètres modifiés')])]
    public function modifierParametresFidelite(): void {}

    #[OA\Get(path: '/staff/menu', tags: ['Personnel'], summary: 'Afficher le menu et ses disponibilités', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Articles et états du menu')])]
    public function menuEquipe(): void {}

    #[OA\Put(path: '/staff/menu-du-jour', tags: ['Personnel'], summary: 'Composer le menu du jour', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Menu mis à jour')])]
    public function composerMenu(): void {}

    #[OA\Patch(path: '/staff/articles/{article}/disponibilite', tags: ['Personnel'], summary: 'Modifier la disponibilité d’un article', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Disponibilité modifiée')])]
    public function disponibiliteArticle(): void {}

    #[OA\Get(path: '/staff/commandes', tags: ['Personnel'], summary: 'Afficher les commandes du service', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Commandes à préparer')])]
    public function commandesService(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/demarrer', tags: ['Personnel'], summary: 'Démarrer la préparation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Préparation démarrée')])]
    public function demarrerPreparation(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/prete', tags: ['Personnel'], summary: 'Marquer une commande prête', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Commande prête')])]
    public function commandePrete(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/cloturer', tags: ['Personnel'], summary: 'Clôturer une commande', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Commande clôturée')])]
    public function cloturerCommande(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/notifier', tags: ['Personnel'], summary: 'Notifier le client', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Client notifié')])]
    public function notifierClient(): void {}

    #[OA\Post(path: '/staff/commandes/{commande}/annuler', tags: ['Personnel'], summary: 'Annuler une commande depuis le service', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'commande', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Commande annulée')])]
    public function annulerDepuisEquipe(): void {}

    #[OA\Get(path: '/gestion/articles', tags: ['Gestion'], summary: 'Lister les articles', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Catalogue des articles')])]
    public function articles(): void {}

    #[OA\Post(path: '/gestion/articles', tags: ['Gestion'], summary: 'Créer un article', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Article créé')])]
    public function creerArticle(): void {}

    #[OA\Put(path: '/gestion/articles/{article}', tags: ['Gestion'], summary: 'Modifier un article', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Article modifié')])]
    public function modifierArticle(): void {}

    #[OA\Patch(path: '/gestion/articles/{article}', tags: ['Gestion'], summary: 'Modifier partiellement un article', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Article modifié')])]
    public function modifierArticlePartiel(): void {}

    #[OA\Delete(path: '/gestion/articles/{article}', tags: ['Gestion'], summary: 'Supprimer un article (administrateur)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Article supprimé')])]
    public function supprimerArticle(): void {}

    #[OA\Get(path: '/gestion/promotions', tags: ['Gestion'], summary: 'Lister les promotions', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Promotions existantes')])]
    public function promotions(): void {}

    #[OA\Post(path: '/gestion/promotions', tags: ['Gestion'], summary: 'Créer une promotion', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Promotion créée')])]
    public function creerPromotion(): void {}

    #[OA\Put(path: '/gestion/promotions/{promotion}', tags: ['Gestion'], summary: 'Modifier une promotion', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'promotion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Promotion modifiée')])]
    public function modifierPromotion(): void {}

    #[OA\Patch(path: '/gestion/promotions/{promotion}', tags: ['Gestion'], summary: 'Modifier partiellement une promotion', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'promotion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Promotion modifiée')])]
    public function modifierPromotionPartielle(): void {}

    #[OA\Delete(path: '/gestion/promotions/{promotion}', tags: ['Gestion'], summary: 'Supprimer une promotion', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'promotion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Promotion supprimée')])]
    public function supprimerPromotion(): void {}
}
