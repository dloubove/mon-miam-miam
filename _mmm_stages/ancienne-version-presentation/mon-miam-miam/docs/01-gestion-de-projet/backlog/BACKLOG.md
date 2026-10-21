🇫🇷 Français | [🇬🇧 English](BACKLOG.en.md)

# Backlog produit — Mon Miam Miam

Version 1.0 — 29/09/2026 — Product Owner : Dan
Sources : cahier des charges V1.0.2 et compte rendu de la réunion n°2.

## 1. Cadre du projet

**Période** : 30/09/2026 → 21/10/2026. Sprint 0 de conception, puis 3 sprints de développement d'environ une semaine.

**Équipe et rôles (réunion n°2)**

| Rôle | Personne(s) |
|---|---|
| Product Owner | Dan |
| Scrum Master (rotation chaque semaine) | Kadir en premier |
| Back-end (Laravel) | Julio, Dan |
| Front-end (React, TailwindCSS, DaisyUI) et Figma | Nabil, Nathan |
| Conception base de données (MCD/MLD) | Siméon |
| Diagrammes UML (Draw.io) | Julio, Kadir |

**Outils** : Jira, GitHub, Figma, Vercel, PostgreSQL, DataGrip, Mocodo, Draw.io, Postman, GitHub Actions.

**Calendrier**

| Sprint | Dates | Objectif |
|---|---|---|
| Sprint 0 | 29/09 → 01/10 | Conception : Jira, dépôt, Figma, MCD/MLD, UML |
| Sprint 1 | 02/10 → 08/10 | Comptes, rôles, menu, accueil |
| Sprint 2 | 09/10 → 15/10 | Cycle de commande complet et fidélité |
| Sprint 3 | 16/10 → 21/10 | Parrainage, réclamations, statistiques, tests, déploiement et livrables |

**Priorités (MoSCoW)** : **Must** = indispensable, **Should** = important, **Could** = si le temps le permet.
**Points** : estimation proposée en suite de Fibonacci, à réviser en Planning Poker par les développeurs.
**Équipe** : Back, Front, BDD ou Doc, à titre indicatif.

**Definition of Done (à valider avec l'équipe)**
- Code relu par au moins un pair via pull request, puis fusionné sur `develop`.
- Tests unitaires écrits et au vert.
- Conforme à la maquette Figma et à la charte (primaire #cfbd97, secondaire #000000).
- Fonctionnel sur Chrome, Firefox et Safari.
- Critères d'acceptation validés par le Product Owner à la Sprint Review.
- Carte Jira déplacée en Done.

**Contraintes transverses du cahier des charges (à respecter dans toutes les stories)**
- Architecture MVC (Laravel côté back-end) et API REST.
- Application web compatible avec tous les navigateurs.
- Conformité RGPD et sécurité des données.
- Respect de la charte graphique.
- Tableaux remplis dynamiquement en AJAX (mise à jour automatique).

**Hors périmètre pour l'instant** : application mobile, gestion de plusieurs restaurants (évolution future à documenter dans MM-44) et paiement en ligne (bonus, MM-40).

## 2. Sprint 0 — Conception (29/09 → 01/10)

**Objectif** : disposer d'une base de travail claire (maquettes, base de données, diagrammes, outils) avant de coder.

| ID | Tâche | Responsable(s) | Échéance |
|---|---|---|---|
| S0-01 | Créer l'espace de travail Jira (projet, backlog, board, sprints) | Dan | 30/09 (2 jours max) |
| S0-02 | Créer le dépôt GitHub (dossiers frontend/backend, branches `main`, `develop`, `feature/*`) | Dan, Julio | 30/09 |
| S0-03 | Prototype Figma fonctionnel : accueil, authentification, espaces étudiant, employé, gérant et administrateur | Nabil, Nathan | 01/10 (3 jours max) |
| S0-04 | Charte graphique (police, couleurs #cfbd97 et #000000) dans Figma | Nabil, Nathan | 01/10 |
| S0-05 | Déployer la maquette sur Vercel | Nabil, Nathan | 01/10 |
| S0-06 | MCD et MLD (Mocodo, PostgreSQL, DataGrip) | Siméon | 2 jours max |
| S0-07 | Diagramme de cas d'utilisation UML (Draw.io) | Julio, Kadir | 01/10 |
| S0-08 | Diagramme de séquence UML (Draw.io) | Julio, Kadir | 01/10 |
| S0-09 | Initialiser Laravel et la connexion PostgreSQL | Julio, Dan | 01/10 |
| S0-10 | Compte rendu de chaque réunion d'équipe | Scrum Master | En continu |

## 3. Sprint 1 — Comptes, rôles, menu et accueil (02/10 → 08/10)

**Objectif** : un étudiant peut créer un compte, se connecter et consulter le menu ; l'administration gère le menu et les employés.

| ID | User story | Critères d'acceptation | Prio | Pts | Équipe |
|---|---|---|---|---|---|
| MM-49 | En tant que développeur front-end, je veux un projet React configuré (Vite, TailwindCSS, DaisyUI aux couleurs de la charte, routage, layouts par rôle) afin de développer les écrans de façon homogène. | Thème DaisyUI aux couleurs #cfbd97 et #000000. Routes protégées par rôle. Lancement en une commande. | Must | 3 | Front |
| MM-01 | En tant qu'étudiant, je veux créer un compte (nom, email, téléphone, localisation, mot de passe) afin de pouvoir commander. | Mot de passe avec au moins 1 majuscule et 1 chiffre. Email unique. Messages d'erreur clairs. | Must | 5 | Back, Front |
| MM-02 | En tant qu'utilisateur, je veux me connecter et me déconnecter de façon sécurisée afin d'accéder à mon espace. | Mot de passe haché. Redirection vers l'espace correspondant au rôle. | Must | 3 | Back, Front |
| MM-03 | En tant qu'utilisateur, je veux récupérer mon mot de passe par un lien afin de ne pas perdre mon compte. | Lien envoyé par email, à durée limitée. Nouveau mot de passe soumis aux mêmes règles. | Should | 3 | Back, Front |
| MM-04 | En tant qu'administrateur, je veux que chaque rôle (étudiant, employé, gérant, admin) n'accède qu'à son espace afin de protéger les données. | Accès refusé (403) aux espaces non autorisés. Vérifié par tests. | Must | 5 | Back |
| MM-05 | En tant qu'étudiant, je veux voir sur la page d'accueil le menu du jour, les promotions et les événements afin de choisir rapidement. | Contenu alimenté par l'API. Affichage responsive. | Must | 5 | Front, Back |
| MM-06 | En tant qu'étudiant, je veux consulter le menu (catégories, prix, disponibilité) afin de préparer ma commande. | Articles épuisés signalés. Tableaux et listes mis à jour dynamiquement (AJAX). | Must | 3 | Front, Back |
| MM-07 | En tant qu'administrateur, je veux créer, modifier et supprimer les éléments du menu afin de le tenir à jour. | CRUD complet avec validation des champs. | Must | 5 | Back, Front |
| MM-08 | En tant qu'administrateur ou gérant, je veux ajouter des comptes employés (l'administrateur peut aussi les modifier et les supprimer) afin de leur donner accès à l'application. | Création avec attribution du rôle. Suppression réservée à l'admin. | Must | 5 | Back, Front |
| MM-09 | En tant qu'administrateur, je veux créer des promotions et des événements sous forme d'affiches afin de stimuler les ventes. | Upload d'image, dates de début et de fin. Affichage sur l'accueil. | Should | 5 | Back, Front |
| MM-10 | En tant que visiteur, je veux être informé de l'usage des cookies et donner mon consentement afin que mes données soient protégées (RGPD). | Bandeau à la première visite. Choix mémorisé. | Must | 2 | Front |
| MM-11 | En tant qu'équipe, je veux des migrations et des seeders issus du MLD afin de partager une base de données identique. | Comptes de test pour chaque rôle. Base recréable en une commande. | Must | 3 | BDD |
| MM-12 | En tant qu'équipe, je veux des tests unitaires sur l'authentification et les rôles afin de sécuriser ce socle. | Tests au vert sur inscription, connexion et accès par rôle. | Must | 3 | Back |
| MM-13 | En tant qu'équipe, je veux un plan de gestion des risques afin d'anticiper délais, bugs critiques et pannes serveur. | Risques identifiés avec mesures d'atténuation. | Must | 2 | Doc |

## 4. Sprint 2 — Commande et fidélité (09/10 → 15/10)

**Objectif** : un étudiant commande, les employés traitent la commande, et les points de fidélité fonctionnent. L'application est déployée en ligne.

| ID | User story | Critères d'acceptation | Prio | Pts | Équipe |
|---|---|---|---|---|---|
| MM-14 | En tant qu'étudiant, je veux ajouter des articles à un panier et modifier les quantités afin de préparer ma commande. | Total recalculé automatiquement. Suppression d'un article possible. | Must | 5 | Front, Back |
| MM-15 | En tant qu'étudiant, je veux valider ma commande sur place (avec heure d'arrivée) ou en livraison (avec localisation) afin d'être servi. | Choix du mode obligatoire. Commande enregistrée avec statut « en attente ». | Must | 8 | Back, Front |
| MM-16 | En tant qu'étudiant, je veux consulter l'historique de mes commandes et leurs détails afin de suivre mes achats. | Liste triée par date. Détail de chaque commande. Statut de la commande visible. | Must | 3 | Front, Back |
| MM-17 | En tant qu'étudiant, je veux laisser un commentaire une fois ma commande livrée afin de donner mon avis. | Possible uniquement pour une commande livrée. | Should | 2 | Back, Front |
| MM-18 | En tant qu'employé, je veux consulter, préparer et valider les commandes afin de traiter efficacement le flux. | Tableau actualisé automatiquement (AJAX). Statuts : en attente, en préparation, prête ou en livraison, livrée. Changements de statut tracés. | Must | 8 | Front, Back |
| MM-19 | En tant qu'employé, je veux modifier temporairement le menu (plat épuisé, plat du jour) afin d'informer les clients en temps réel. | Changement visible immédiatement côté étudiant. | Must | 3 | Back, Front |
| MM-20 | En tant que gérant, je veux superviser l'état des commandes en temps réel afin de surveiller le service. | Vue globale de toutes les commandes en cours. | Should | 3 | Front, Back |
| MM-21 | En tant qu'étudiant, je veux gagner automatiquement des points à chaque commande afin d'être récompensé (1 000 F dépensés = 1 point). | Points ajoutés au solde après validation de la commande. Conversion paramétrable. | Should | 5 | Back |
| MM-22 | En tant qu'étudiant, je veux voir mon solde et l'historique de mes points afin de suivre ma fidélité. | Solde dans le profil. Tableau des points par commande. | Should | 3 | Front, Back |
| MM-23 | En tant qu'étudiant, je veux utiliser mes points pour réduire le montant d'une commande (15 points = 1 000 F) afin de profiter de ma fidélité. | Seuil minimal vérifié. Réduction calculée et points déduits. | Should | 5 | Back, Front |
| MM-24 | En tant qu'administrateur, je veux configurer les paramètres de l'application (horaires d'ouverture, taux de conversion, durée de validité des points, points de parrainage) afin d'adapter les règles. | Paramètres modifiables sans toucher au code, dont les points offerts au parrain. | Should | 3 | Back, Front |
| MM-25 | En tant qu'équipe, je veux des tests unitaires sur les commandes et les points afin de garantir les calculs. | Tests au vert sur création de commande, attribution et utilisation des points. | Must | 3 | Back |
| MM-26 | En tant qu'équipe, je veux une analyse budgétaire du projet afin de l'inclure aux livrables. | Document rédigé et relu par le PO. | Must | 2 | Doc |
| MM-27 | En tant qu'équipe, je veux les mentions légales et les aspects juridiques associés afin d'être conforme. | Mentions légales et politique de confidentialité intégrées au site (générateur en ligne possible). | Must | 2 | Doc, Front |
| MM-42 | CI/CD GitHub Actions et déploiement : backend sur une plateforme cloud avec base PostgreSQL de production, frontend sur Vercel | `develop` déployée automatiquement sur un environnement de test, `main` sur la production. Application accessible en ligne. | Must | 5 | Back |

## 5. Sprint 3 — Parrainage, réclamations, statistiques et livraison (16/10 → 21/10)

**Objectif** : compléter les fonctionnalités, valider la qualité et livrer une version déployée avec toute la documentation.

**Attention** : ce sprint est chargé. À la fin du Sprint 1, comparer la vélocité réelle avec ces estimations. Si nécessaire, retirer d'abord les éléments **Could**, puis certains **Should** (statistiques avant réclamations).

### 5.1 Fonctionnalités

| ID | User story | Critères d'acceptation | Prio | Pts | Équipe |
|---|---|---|---|---|---|
| MM-28 | En tant qu'étudiant, je veux générer mon code de parrainage unique afin de le partager. | Un seul code par utilisateur, unique. | Should | 3 | Back, Front |
| MM-29 | En tant que nouvel étudiant, je veux saisir un code de parrainage à l'inscription afin de rattacher mon compte à mon parrain. | Code invalide refusé. Lien parrain/filleul enregistré. Moment de saisie (inscription ou première commande) à confirmer avec le client. | Should | 3 | Back, Front |
| MM-30 | En tant que parrain, je veux gagner des points quand mon filleul passe sa première commande réussie afin d'être récompensé. | Récompense attribuée une seule fois. Statut « récompense attribuée ». | Should | 5 | Back |
| MM-31 | En tant que parrain, je veux suivre la liste de mes filleuls et l'état de leur première commande afin de savoir si j'ai été récompensé. | Liste avec statut par filleul. | Should | 2 | Front, Back |
| MM-32 | En tant qu'étudiant, je veux signaler un problème sur une commande et suivre l'état de ma réclamation afin d'obtenir une solution. | Réclamation liée à une commande. Statuts visibles. | Should | 5 | Back, Front |
| MM-33 | En tant qu'employé, je veux consulter et répondre aux réclamations afin de les traiter. | Tableau de bord des réclamations. Réponse proposée. | Should | 3 | Front, Back |
| MM-34 | En tant que gérant, je veux valider ou rejeter les réponses proposées, et en tant qu'administrateur suivre les réclamations, afin de garder le contrôle. | Décision tracée. Vue de suivi pour l'admin. | Should | 3 | Back, Front |
| MM-50 | En tant qu'administrateur, je veux valider manuellement une réclamation de points afin de corriger le solde d'un étudiant en cas d'erreur. | Ajustement de points tracé (motif, date, auteur). Solde mis à jour. | Should | 3 | Back, Front |
| MM-35 | En tant qu'employé, je veux voir les statistiques de ventes de la semaine afin d'ajuster mon travail. | Ventes et commandes de la semaine. | Should | 5 | Back, Front |
| MM-36 | En tant que gérant ou administrateur, je veux voir les statistiques générales (ventes, commandes, fidélité, parrainage) afin de piloter le restaurant. | Graphiques et totaux actualisés automatiquement (AJAX). | Should | 5 | Back, Front |
| MM-37 | En tant qu'administrateur, je veux que les points expirent automatiquement après la durée définie (ex. 12 mois) afin de respecter la politique de fidélité. | Tâche planifiée. Points expirés retirés du solde. | Could | 3 | Back |
| MM-38 | En tant qu'étudiant, je veux voir les 10 meilleurs clients avec filtre jour, semaine ou mois afin de me comparer aux autres. | Classement par nombre de commandes. | Could | 3 | Back, Front |
| MM-39 | En tant qu'étudiant, je veux participer à des mini-jeux et événements afin de gagner des prix ou des points. | Au moins un mini-jeu fonctionnel avec gain de points. | Could | 8 | Front, Back |
| MM-40 | En tant qu'étudiant, je veux payer en ligne via un agrégateur (Mobile Money, carte) afin d'éviter les paiements sur place. | Intégration CinetPay ou Stripe. Fonctionnalité bonus, non obligatoire. | Could | 13 | Back, Front |

### 5.2 Qualité, déploiement et livrables

| ID | Tâche | Critères d'acceptation | Prio | Pts | Équipe |
|---|---|---|---|---|---|
| MM-51 | Tests unitaires du parrainage (génération et utilisation des codes, attribution des récompenses) | Tests au vert sur les trois cas. | Must | 2 | Back |
| MM-41 | Tests fonctionnels et end-to-end (de la commande à la livraison) et rapport de test | Fonctionnalités critiques couvertes (connexion, commande, fidélité). Rapport rédigé. | Must | 5 | Tous |
| MM-43 | Documentation de l'API REST | Endpoints documentés (Postman ou OpenAPI). | Must | 3 | Back |
| MM-44 | Documentation technique : architecture MVC, guide d'installation, schémas de la base de données, charte graphique | Installation reproductible en local et en production. Évolution future vers plusieurs restaurants mentionnée. | Must | 3 | Doc |
| MM-45 | Manuel d'utilisation par rôle (étudiant, employé, gérant, administrateur) | Un guide par rôle avec captures d'écran. | Must | 3 | Doc |
| MM-46 | Version test en préproduction et bêta-test avec un groupe restreint | Retours collectés et priorisés. | Must | 3 | Tous |
| MM-47 | Vérifier la compatibilité navigateurs (Chrome, Firefox, Safari) | Parcours principaux validés sur les 3 navigateurs. | Must | 2 | Front |
| MM-48 | Rapport d'avancement de chaque sprint et comptes rendus de réunion | Un rapport par sprint, déposé dans le dépôt. | Must | 2 | Scrum Master |

## 6. Dépendances à surveiller

- MM-06 à MM-08 et MM-14 dépendent du MLD (S0-06) et des migrations (MM-11).
- Le développement front commence après la maquette Figma (01/10).
- Les points de fidélité (MM-21) exigent des commandes validées (MM-18).
- Le parrainage (MM-30) exige l'inscription (MM-01) et les commandes (MM-15).
- La CI/CD et le déploiement (MM-42) sont planifiés au Sprint 2 pour avoir une application en ligne tôt. Le choix de l'hébergement cloud doit être fait dès le Sprint 1.
- MM-49 (initialisation du frontend) précède toutes les autres tâches front-end.
- MM-50 exige les réclamations (MM-32) et les points (MM-21).
