🇫🇷 Français | [🇬🇧 English](BACKLOG_SAISIE_JIRA.en.md)

# Backlog à saisir dans Jira — Mon Miam Miam

Ce document sert à remplir Jira **à la main**, sans import CSV. Chaque élément est prêt à copier-coller : titre, type, sprint, points, étiquettes et description.

## Comment procéder

1. **Créer un nouveau projet** (modèle Scrum, clé **MM**) : Jira ne remet jamais la numérotation à zéro dans un projet existant, même si on supprime les éléments.
2. **Créer les 4 sprints** (voir tableau ci-dessous).
3. **Créer les 61 éléments dans l'ordre exact du tableau « Ordre de création »** (titre et type seulement). Jira numérote dans l'ordre de création, epics compris : si on crée les epics en premier, les stories commencent à 11.
4. **Créer les 10 epics en dernier.**
5. **Rattacher chaque élément à son epic** (filtre par étiquette, puis champ **Parent**) et renseigner le **sprint**, les **points** et les **étiquettes**.
6. **Coller les descriptions** à partir des blocs de la section « Les éléments par epic ». Cocher la case ☐ de chaque titre au fur et à mesure, puis contrôler avec les totaux de la dernière section.

**Gain de temps** : saisis d'abord tous les titres, epics, sprints, points et étiquettes (l'essentiel pour organiser le backlog). Colle les descriptions sprint par sprint, juste avant chaque Sprint Planning.

**Priorité** : le champ Priorité n'est pas utilisé. La priorité MoSCoW est portée par la première étiquette (`must`, `should` ou `could`).

## Les sprints à créer

| Sprint | Dates | Éléments | Points |
|---|---|---|---|
| Sprint 0 | 29/09 → 01/10 | 10 | 0 |
| Sprint 1 | 02/10 → 08/10 | 14 | 52 |
| Sprint 2 | 09/10 → 15/10 | 15 | 60 |
| Sprint 3 | 16/10 → 21/10 | 22 | 87 |

Les dates se renseignent au démarrage de chaque sprint.

## Les epics à créer

| Epic | Description à coller |
|---|---|
| Conception et socle technique | Maquettes, base de données, diagrammes UML, dépôt, initialisation Laravel et React. |
| Comptes et accès | Inscription, connexion, récupération du mot de passe, rôles et consentement cookies. |
| Menu et accueil | Page d'accueil, consultation et gestion du menu, promotions et événements. |
| Commande | Panier, commande sur place ou en livraison, historique, gestion par les employés et supervision par le gérant. |
| Fidélité et parrainage | Points de fidélité, utilisation en réduction, codes de parrainage et récompenses. |
| Réclamations | Dépôt, traitement, validation et suivi des réclamations, y compris celles sur les points. |
| Administration et statistiques | Comptes employés, paramètres de l'application et statistiques. |
| Qualité, tests et déploiement | Tests unitaires, fonctionnels et E2E, CI/CD, bêta-test, compatibilité navigateurs. |
| Documentation et gestion de projet | Risques, budget, aspects juridiques, documentation technique et API, manuels, rapports. |
| Bonus | Top 10 des clients, mini-jeux, paiement en ligne. |

## Ordre de création

Crée les éléments **exactement dans cet ordre**. Avec la clé de projet MM, le numéro affiché par Jira doit correspondre à la colonne « N° Jira attendu » : MM-01 devient MM-1, et ainsi de suite. Les éléments S0 prennent les numéros 52 à 61 et les epics 62 à 71.

| N° Jira attendu | Élément | Type | Sprint |
|---|---|---|---|
| MM-1 | [MM-01] Inscription étudiant | Story | Sprint 1 |
| MM-2 | [MM-02] Connexion et déconnexion sécurisées | Story | Sprint 1 |
| MM-3 | [MM-03] Récupération du mot de passe | Story | Sprint 1 |
| MM-4 | [MM-04] Rôles et permissions | Story | Sprint 1 |
| MM-5 | [MM-05] Page d'accueil | Story | Sprint 1 |
| MM-6 | [MM-06] Consultation du menu | Story | Sprint 1 |
| MM-7 | [MM-07] CRUD du menu (admin) | Story | Sprint 1 |
| MM-8 | [MM-08] Gestion des comptes employés | Story | Sprint 1 |
| MM-9 | [MM-09] Promotions et événements (affiches) | Story | Sprint 1 |
| MM-10 | [MM-10] Bandeau de consentement cookies | Story | Sprint 1 |
| MM-11 | [MM-11] Migrations et seeders | Task | Sprint 1 |
| MM-12 | [MM-12] Tests unitaires authentification et rôles | Task | Sprint 1 |
| MM-13 | [MM-13] Plan de gestion des risques | Task | Sprint 1 |
| MM-14 | [MM-14] Panier | Story | Sprint 2 |
| MM-15 | [MM-15] Validation de commande (sur place / livraison) | Story | Sprint 2 |
| MM-16 | [MM-16] Historique des commandes | Story | Sprint 2 |
| MM-17 | [MM-17] Commentaire après livraison | Story | Sprint 2 |
| MM-18 | [MM-18] Gestion des commandes (employé) | Story | Sprint 2 |
| MM-19 | [MM-19] Mise à jour temporaire du menu (employé) | Story | Sprint 2 |
| MM-20 | [MM-20] Supervision des commandes (gérant) | Story | Sprint 2 |
| MM-21 | [MM-21] Gain automatique de points | Story | Sprint 2 |
| MM-22 | [MM-22] Solde et historique des points | Story | Sprint 2 |
| MM-23 | [MM-23] Utilisation des points en réduction | Story | Sprint 2 |
| MM-24 | [MM-24] Paramètres de l'application | Story | Sprint 2 |
| MM-25 | [MM-25] Tests unitaires commandes et points | Task | Sprint 2 |
| MM-26 | [MM-26] Analyse budgétaire | Task | Sprint 2 |
| MM-27 | [MM-27] Mentions légales et aspects juridiques | Task | Sprint 2 |
| MM-28 | [MM-28] Génération du code de parrainage | Story | Sprint 3 |
| MM-29 | [MM-29] Utilisation du code de parrainage | Story | Sprint 3 |
| MM-30 | [MM-30] Récompense du parrain | Story | Sprint 3 |
| MM-31 | [MM-31] Suivi des filleuls | Story | Sprint 3 |
| MM-32 | [MM-32] Réclamation étudiant | Story | Sprint 3 |
| MM-33 | [MM-33] Traitement des réclamations (employé) | Story | Sprint 3 |
| MM-34 | [MM-34] Validation des réclamations (gérant/admin) | Story | Sprint 3 |
| MM-35 | [MM-35] Statistiques hebdomadaires (employé) | Story | Sprint 3 |
| MM-36 | [MM-36] Statistiques générales (gérant/admin) | Story | Sprint 3 |
| MM-37 | [MM-37] Expiration automatique des points | Story | Sprint 3 |
| MM-38 | [MM-38] Top 10 des meilleurs clients | Story | Sprint 3 |
| MM-39 | [MM-39] Mini-jeux et événements | Story | Sprint 3 |
| MM-40 | [MM-40] Paiement en ligne (bonus) | Story | Sprint 3 |
| MM-41 | [MM-41] Tests fonctionnels, E2E et rapport de test | Task | Sprint 3 |
| MM-42 | [MM-42] CI/CD et déploiement | Task | Sprint 2 |
| MM-43 | [MM-43] Documentation de l'API REST | Task | Sprint 3 |
| MM-44 | [MM-44] Documentation technique | Task | Sprint 3 |
| MM-45 | [MM-45] Manuel d'utilisation par rôle | Task | Sprint 3 |
| MM-46 | [MM-46] Version test et bêta-test | Task | Sprint 3 |
| MM-47 | [MM-47] Compatibilité navigateurs | Task | Sprint 3 |
| MM-48 | [MM-48] Rapports de sprint et comptes rendus | Task | Sprint 3 |
| MM-49 | [MM-49] Initialiser le frontend React | Story | Sprint 1 |
| MM-50 | [MM-50] Validation manuelle des réclamations de points | Story | Sprint 3 |
| MM-51 | [MM-51] Tests unitaires du parrainage | Task | Sprint 3 |
| MM-52 | [S0-01] Créer l'espace de travail Jira | Task | Sprint 0 |
| MM-53 | [S0-02] Créer le dépôt GitHub | Task | Sprint 0 |
| MM-54 | [S0-03] Prototype Figma fonctionnel | Task | Sprint 0 |
| MM-55 | [S0-04] Charte graphique dans Figma | Task | Sprint 0 |
| MM-56 | [S0-05] Déployer la maquette sur Vercel | Task | Sprint 0 |
| MM-57 | [S0-06] MCD et MLD | Task | Sprint 0 |
| MM-58 | [S0-07] Diagramme de cas d'utilisation UML | Task | Sprint 0 |
| MM-59 | [S0-08] Diagramme de séquence UML | Task | Sprint 0 |
| MM-60 | [S0-09] Initialiser Laravel et PostgreSQL | Task | Sprint 0 |
| MM-61 | [S0-10] Comptes rendus de réunion | Task | Sprint 0 |
| MM-62 | Conception et socle technique | Epic | — |
| MM-63 | Comptes et accès | Epic | — |
| MM-64 | Menu et accueil | Epic | — |
| MM-65 | Commande | Epic | — |
| MM-66 | Fidélité et parrainage | Epic | — |
| MM-67 | Réclamations | Epic | — |
| MM-68 | Administration et statistiques | Epic | — |
| MM-69 | Qualité, tests et déploiement | Epic | — |
| MM-70 | Documentation et gestion de projet | Epic | — |
| MM-71 | Bonus | Epic | — |

## Les éléments par epic

### Conception et socle technique

12 éléments, 6 points.

#### ☐ [S0-01] Créer l'espace de travail Jira

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Créer l'espace de travail Jira (projet, backlog, board, sprints)
Responsable(s) suggéré(s) : Dan
Échéance : 30/09 (2 jours max)
```

#### ☐ [S0-02] Créer le dépôt GitHub

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Créer le dépôt GitHub (dossiers frontend/backend, branches `main`, `develop`, `feature/*`)
Responsable(s) suggéré(s) : Dan, Julio
Échéance : 30/09
```

#### ☐ [S0-03] Prototype Figma fonctionnel

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Prototype Figma fonctionnel : accueil, authentification, espaces étudiant, employé, gérant et administrateur
Responsable(s) suggéré(s) : Nabil, Nathan
Échéance : 01/10 (3 jours max)
```

#### ☐ [S0-04] Charte graphique dans Figma

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Charte graphique (police, couleurs #cfbd97 et #000000) dans Figma
Responsable(s) suggéré(s) : Nabil, Nathan
Échéance : 01/10
```

#### ☐ [S0-05] Déployer la maquette sur Vercel

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Déployer la maquette sur Vercel
Responsable(s) suggéré(s) : Nabil, Nathan
Échéance : 01/10
```

#### ☐ [S0-06] MCD et MLD

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
MCD et MLD (Mocodo, PostgreSQL, DataGrip)
Responsable(s) suggéré(s) : Siméon
Échéance : 2 jours max
```

#### ☐ [S0-07] Diagramme de cas d'utilisation UML

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Diagramme de cas d'utilisation UML (Draw.io)
Responsable(s) suggéré(s) : Julio, Kadir
Échéance : 01/10
```

#### ☐ [S0-08] Diagramme de séquence UML

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Diagramme de séquence UML (Draw.io)
Responsable(s) suggéré(s) : Julio, Kadir
Échéance : 01/10
```

#### ☐ [S0-09] Initialiser Laravel et PostgreSQL

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Initialiser Laravel et la connexion PostgreSQL
Responsable(s) suggéré(s) : Julio, Dan
Échéance : 01/10
```

#### ☐ [S0-10] Comptes rendus de réunion

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 0

**Étiquettes** : `conception`

**Description à coller** :

```
Compte rendu de chaque réunion d'équipe
Responsable(s) suggéré(s) : Scrum Master
Échéance : En continu
```

#### ☐ [MM-11] Migrations et seeders

**Epic** : Conception et socle technique · **Type** : Task · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `must` `base-de-donnees` `bdd`

**Description à coller** :

```
En tant qu'équipe, je veux des migrations et des seeders issus du MLD afin de partager une base de données identique.

Critères d'acceptation : Comptes de test pour chaque rôle. Base recréable en une commande.
```

#### ☐ [MM-49] Initialiser le frontend React

**Epic** : Conception et socle technique · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `must` `frontend` `front`

**Description à coller** :

```
En tant que développeur front-end, je veux un projet React configuré (Vite, TailwindCSS, DaisyUI aux couleurs de la charte, routage, layouts par rôle) afin de développer les écrans de façon homogène.

Critères d'acceptation : Thème DaisyUI aux couleurs #cfbd97 et #000000. Routes protégées par rôle. Lancement en une commande.
```

### Comptes et accès

5 éléments, 18 points.

#### ☐ [MM-01] Inscription étudiant

**Epic** : Comptes et accès · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `must` `authentification` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux créer un compte (nom, email, téléphone, localisation, mot de passe) afin de pouvoir commander.

Critères d'acceptation : Mot de passe avec au moins 1 majuscule et 1 chiffre. Email unique. Messages d'erreur clairs.
```

#### ☐ [MM-02] Connexion et déconnexion sécurisées

**Epic** : Comptes et accès · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `must` `authentification` `back` `front`

**Description à coller** :

```
En tant qu'utilisateur, je veux me connecter et me déconnecter de façon sécurisée afin d'accéder à mon espace.

Critères d'acceptation : Mot de passe haché. Redirection vers l'espace correspondant au rôle.
```

#### ☐ [MM-03] Récupération du mot de passe

**Epic** : Comptes et accès · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `should` `authentification` `back` `front`

**Description à coller** :

```
En tant qu'utilisateur, je veux récupérer mon mot de passe par un lien afin de ne pas perdre mon compte.

Critères d'acceptation : Lien envoyé par email, à durée limitée. Nouveau mot de passe soumis aux mêmes règles.
```

#### ☐ [MM-04] Rôles et permissions

**Epic** : Comptes et accès · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `must` `authentification` `back`

**Description à coller** :

```
En tant qu'administrateur, je veux que chaque rôle (étudiant, employé, gérant, admin) n'accède qu'à son espace afin de protéger les données.

Critères d'acceptation : Accès refusé (403) aux espaces non autorisés. Vérifié par tests.
```

#### ☐ [MM-10] Bandeau de consentement cookies

**Epic** : Comptes et accès · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 2

**Étiquettes** : `must` `rgpd` `front`

**Description à coller** :

```
En tant que visiteur, je veux être informé de l'usage des cookies et donner mon consentement afin que mes données soient protégées (RGPD).

Critères d'acceptation : Bandeau à la première visite. Choix mémorisé.
```

### Menu et accueil

4 éléments, 18 points.

#### ☐ [MM-05] Page d'accueil

**Epic** : Menu et accueil · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `must` `menu-accueil` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux voir sur la page d'accueil le menu du jour, les promotions et les événements afin de choisir rapidement.

Critères d'acceptation : Contenu alimenté par l'API. Affichage responsive.
```

#### ☐ [MM-06] Consultation du menu

**Epic** : Menu et accueil · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `must` `menu-accueil` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux consulter le menu (catégories, prix, disponibilité) afin de préparer ma commande.

Critères d'acceptation : Articles épuisés signalés. Tableaux et listes mis à jour dynamiquement (AJAX).
```

#### ☐ [MM-07] CRUD du menu (admin)

**Epic** : Menu et accueil · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `must` `menu-accueil` `back` `front`

**Description à coller** :

```
En tant qu'administrateur, je veux créer, modifier et supprimer les éléments du menu afin de le tenir à jour.

Critères d'acceptation : CRUD complet avec validation des champs.
```

#### ☐ [MM-09] Promotions et événements (affiches)

**Epic** : Menu et accueil · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `should` `menu-accueil` `back` `front`

**Description à coller** :

```
En tant qu'administrateur, je veux créer des promotions et des événements sous forme d'affiches afin de stimuler les ventes.

Critères d'acceptation : Upload d'image, dates de début et de fin. Affichage sur l'accueil.
```

### Commande

7 éléments, 32 points.

#### ☐ [MM-14] Panier

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 5

**Étiquettes** : `must` `commande` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux ajouter des articles à un panier et modifier les quantités afin de préparer ma commande.

Critères d'acceptation : Total recalculé automatiquement. Suppression d'un article possible.
```

#### ☐ [MM-15] Validation de commande (sur place / livraison)

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 8

**Étiquettes** : `must` `commande` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux valider ma commande sur place (avec heure d'arrivée) ou en livraison (avec localisation) afin d'être servi.

Critères d'acceptation : Choix du mode obligatoire. Commande enregistrée avec statut « en attente ».
```

#### ☐ [MM-16] Historique des commandes

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `must` `commande` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux consulter l'historique de mes commandes et leurs détails afin de suivre mes achats.

Critères d'acceptation : Liste triée par date. Détail de chaque commande. Statut de la commande visible.
```

#### ☐ [MM-17] Commentaire après livraison

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 2

**Étiquettes** : `should` `commande` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux laisser un commentaire une fois ma commande livrée afin de donner mon avis.

Critères d'acceptation : Possible uniquement pour une commande livrée.
```

#### ☐ [MM-18] Gestion des commandes (employé)

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 8

**Étiquettes** : `must` `commande` `front` `back`

**Description à coller** :

```
En tant qu'employé, je veux consulter, préparer et valider les commandes afin de traiter efficacement le flux.

Critères d'acceptation : Tableau actualisé automatiquement (AJAX). Statuts : en attente, en préparation, prête ou en livraison, livrée. Changements de statut tracés.
```

#### ☐ [MM-19] Mise à jour temporaire du menu (employé)

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `must` `commande` `back` `front`

**Description à coller** :

```
En tant qu'employé, je veux modifier temporairement le menu (plat épuisé, plat du jour) afin d'informer les clients en temps réel.

Critères d'acceptation : Changement visible immédiatement côté étudiant.
```

#### ☐ [MM-20] Supervision des commandes (gérant)

**Epic** : Commande · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `should` `commande` `front` `back`

**Description à coller** :

```
En tant que gérant, je veux superviser l'état des commandes en temps réel afin de surveiller le service.

Critères d'acceptation : Vue globale de toutes les commandes en cours.
```

### Fidélité et parrainage

8 éléments, 29 points.

#### ☐ [MM-21] Gain automatique de points

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 5

**Étiquettes** : `should` `fidelite` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux gagner automatiquement des points à chaque commande afin d'être récompensé (1 000 F dépensés = 1 point).

Critères d'acceptation : Points ajoutés au solde après validation de la commande. Conversion paramétrable.
```

#### ☐ [MM-22] Solde et historique des points

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `should` `fidelite` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux voir mon solde et l'historique de mes points afin de suivre ma fidélité.

Critères d'acceptation : Solde dans le profil. Tableau des points par commande.
```

#### ☐ [MM-23] Utilisation des points en réduction

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 5

**Étiquettes** : `should` `fidelite` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux utiliser mes points pour réduire le montant d'une commande (15 points = 1 000 F) afin de profiter de ma fidélité.

Critères d'acceptation : Seuil minimal vérifié. Réduction calculée et points déduits.
```

#### ☐ [MM-28] Génération du code de parrainage

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `should` `parrainage` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux générer mon code de parrainage unique afin de le partager.

Critères d'acceptation : Un seul code par utilisateur, unique.
```

#### ☐ [MM-29] Utilisation du code de parrainage

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `should` `parrainage` `back` `front`

**Description à coller** :

```
En tant que nouvel étudiant, je veux saisir un code de parrainage à l'inscription afin de rattacher mon compte à mon parrain.

Critères d'acceptation : Code invalide refusé. Lien parrain/filleul enregistré. Moment de saisie (inscription ou première commande) à confirmer avec le client.
```

#### ☐ [MM-30] Récompense du parrain

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 5

**Étiquettes** : `should` `parrainage` `back`

**Description à coller** :

```
En tant que parrain, je veux gagner des points quand mon filleul passe sa première commande réussie afin d'être récompensé.

Critères d'acceptation : Récompense attribuée une seule fois. Statut « récompense attribuée ».
```

#### ☐ [MM-31] Suivi des filleuls

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 2

**Étiquettes** : `should` `parrainage` `front` `back`

**Description à coller** :

```
En tant que parrain, je veux suivre la liste de mes filleuls et l'état de leur première commande afin de savoir si j'ai été récompensé.

Critères d'acceptation : Liste avec statut par filleul.
```

#### ☐ [MM-37] Expiration automatique des points

**Epic** : Fidélité et parrainage · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `could` `fidelite` `back`

**Description à coller** :

```
En tant qu'administrateur, je veux que les points expirent automatiquement après la durée définie (ex. 12 mois) afin de respecter la politique de fidélité.

Critères d'acceptation : Tâche planifiée. Points expirés retirés du solde.
```

### Réclamations

4 éléments, 14 points.

#### ☐ [MM-32] Réclamation étudiant

**Epic** : Réclamations · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 5

**Étiquettes** : `should` `reclamations` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux signaler un problème sur une commande et suivre l'état de ma réclamation afin d'obtenir une solution.

Critères d'acceptation : Réclamation liée à une commande. Statuts visibles.
```

#### ☐ [MM-33] Traitement des réclamations (employé)

**Epic** : Réclamations · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `should` `reclamations` `front` `back`

**Description à coller** :

```
En tant qu'employé, je veux consulter et répondre aux réclamations afin de les traiter.

Critères d'acceptation : Tableau de bord des réclamations. Réponse proposée.
```

#### ☐ [MM-34] Validation des réclamations (gérant/admin)

**Epic** : Réclamations · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `should` `reclamations` `back` `front`

**Description à coller** :

```
En tant que gérant, je veux valider ou rejeter les réponses proposées, et en tant qu'administrateur suivre les réclamations, afin de garder le contrôle.

Critères d'acceptation : Décision tracée. Vue de suivi pour l'admin.
```

#### ☐ [MM-50] Validation manuelle des réclamations de points

**Epic** : Réclamations · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `should` `reclamations` `back` `front`

**Description à coller** :

```
En tant qu'administrateur, je veux valider manuellement une réclamation de points afin de corriger le solde d'un étudiant en cas d'erreur.

Critères d'acceptation : Ajustement de points tracé (motif, date, auteur). Solde mis à jour.
```

### Administration et statistiques

4 éléments, 18 points.

#### ☐ [MM-08] Gestion des comptes employés

**Epic** : Administration et statistiques · **Type** : Story · **Sprint** : Sprint 1 · **Points** : 5

**Étiquettes** : `must` `administration` `back` `front`

**Description à coller** :

```
En tant qu'administrateur ou gérant, je veux ajouter des comptes employés (l'administrateur peut aussi les modifier et les supprimer) afin de leur donner accès à l'application.

Critères d'acceptation : Création avec attribution du rôle. Suppression réservée à l'admin.
```

#### ☐ [MM-24] Paramètres de l'application

**Epic** : Administration et statistiques · **Type** : Story · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `should` `administration` `back` `front`

**Description à coller** :

```
En tant qu'administrateur, je veux configurer les paramètres de l'application (horaires d'ouverture, taux de conversion, durée de validité des points, points de parrainage) afin d'adapter les règles.

Critères d'acceptation : Paramètres modifiables sans toucher au code, dont les points offerts au parrain.
```

#### ☐ [MM-35] Statistiques hebdomadaires (employé)

**Epic** : Administration et statistiques · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 5

**Étiquettes** : `should` `statistiques` `back` `front`

**Description à coller** :

```
En tant qu'employé, je veux voir les statistiques de ventes de la semaine afin d'ajuster mon travail.

Critères d'acceptation : Ventes et commandes de la semaine.
```

#### ☐ [MM-36] Statistiques générales (gérant/admin)

**Epic** : Administration et statistiques · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 5

**Étiquettes** : `should` `statistiques` `back` `front`

**Description à coller** :

```
En tant que gérant ou administrateur, je veux voir les statistiques générales (ventes, commandes, fidélité, parrainage) afin de piloter le restaurant.

Critères d'acceptation : Graphiques et totaux actualisés automatiquement (AJAX).
```

### Qualité, tests et déploiement

7 éléments, 23 points.

#### ☐ [MM-12] Tests unitaires authentification et rôles

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 1 · **Points** : 3

**Étiquettes** : `must` `tests` `back`

**Description à coller** :

```
En tant qu'équipe, je veux des tests unitaires sur l'authentification et les rôles afin de sécuriser ce socle.

Critères d'acceptation : Tests au vert sur inscription, connexion et accès par rôle.
```

#### ☐ [MM-25] Tests unitaires commandes et points

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 2 · **Points** : 3

**Étiquettes** : `must` `tests` `back`

**Description à coller** :

```
En tant qu'équipe, je veux des tests unitaires sur les commandes et les points afin de garantir les calculs.

Critères d'acceptation : Tests au vert sur création de commande, attribution et utilisation des points.
```

#### ☐ [MM-42] CI/CD et déploiement

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 2 · **Points** : 5

**Étiquettes** : `must` `deploiement` `back`

**Description à coller** :

```
CI/CD GitHub Actions et déploiement : backend sur une plateforme cloud avec base PostgreSQL de production, frontend sur Vercel

Critères d'acceptation : `develop` déployée automatiquement sur un environnement de test, `main` sur la production. Application accessible en ligne.
```

#### ☐ [MM-41] Tests fonctionnels, E2E et rapport de test

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 5

**Étiquettes** : `must` `tests` `tous`

**Description à coller** :

```
Tests fonctionnels et end-to-end (de la commande à la livraison) et rapport de test

Critères d'acceptation : Fonctionnalités critiques couvertes (connexion, commande, fidélité). Rapport rédigé.
```

#### ☐ [MM-46] Version test et bêta-test

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `must` `qualite` `tous`

**Description à coller** :

```
Version test en préproduction et bêta-test avec un groupe restreint

Critères d'acceptation : Retours collectés et priorisés.
```

#### ☐ [MM-47] Compatibilité navigateurs

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 2

**Étiquettes** : `must` `qualite` `front`

**Description à coller** :

```
Vérifier la compatibilité navigateurs (Chrome, Firefox, Safari)

Critères d'acceptation : Parcours principaux validés sur les 3 navigateurs.
```

#### ☐ [MM-51] Tests unitaires du parrainage

**Epic** : Qualité, tests et déploiement · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 2

**Étiquettes** : `must` `tests` `back`

**Description à coller** :

```
Tests unitaires du parrainage (génération et utilisation des codes, attribution des récompenses)

Critères d'acceptation : Tests au vert sur les trois cas.
```

### Documentation et gestion de projet

7 éléments, 17 points.

#### ☐ [MM-13] Plan de gestion des risques

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 1 · **Points** : 2

**Étiquettes** : `must` `gestion-projet` `doc`

**Description à coller** :

```
En tant qu'équipe, je veux un plan de gestion des risques afin d'anticiper délais, bugs critiques et pannes serveur.

Critères d'acceptation : Risques identifiés avec mesures d'atténuation.
```

#### ☐ [MM-26] Analyse budgétaire

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 2 · **Points** : 2

**Étiquettes** : `must` `gestion-projet` `doc`

**Description à coller** :

```
En tant qu'équipe, je veux une analyse budgétaire du projet afin de l'inclure aux livrables.

Critères d'acceptation : Document rédigé et relu par le PO.
```

#### ☐ [MM-27] Mentions légales et aspects juridiques

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 2 · **Points** : 2

**Étiquettes** : `must` `juridique` `doc` `front`

**Description à coller** :

```
En tant qu'équipe, je veux les mentions légales et les aspects juridiques associés afin d'être conforme.

Critères d'acceptation : Mentions légales et politique de confidentialité intégrées au site (générateur en ligne possible).
```

#### ☐ [MM-43] Documentation de l'API REST

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `must` `documentation` `back`

**Description à coller** :

```
Documentation de l'API REST

Critères d'acceptation : Endpoints documentés (Postman ou OpenAPI).
```

#### ☐ [MM-44] Documentation technique

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `must` `documentation` `doc`

**Description à coller** :

```
Documentation technique : architecture MVC, guide d'installation, schémas de la base de données, charte graphique

Critères d'acceptation : Installation reproductible en local et en production. Évolution future vers plusieurs restaurants mentionnée.
```

#### ☐ [MM-45] Manuel d'utilisation par rôle

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `must` `documentation` `doc`

**Description à coller** :

```
Manuel d'utilisation par rôle (étudiant, employé, gérant, administrateur)

Critères d'acceptation : Un guide par rôle avec captures d'écran.
```

#### ☐ [MM-48] Rapports de sprint et comptes rendus

**Epic** : Documentation et gestion de projet · **Type** : Task · **Sprint** : Sprint 3 · **Points** : 2

**Étiquettes** : `must` `gestion-projet` `scrum-master`

**Description à coller** :

```
Rapport d'avancement de chaque sprint et comptes rendus de réunion

Critères d'acceptation : Un rapport par sprint, déposé dans le dépôt.
```

### Bonus

3 éléments, 24 points.

#### ☐ [MM-38] Top 10 des meilleurs clients

**Epic** : Bonus · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 3

**Étiquettes** : `could` `engagement` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux voir les 10 meilleurs clients avec filtre jour, semaine ou mois afin de me comparer aux autres.

Critères d'acceptation : Classement par nombre de commandes.
```

#### ☐ [MM-39] Mini-jeux et événements

**Epic** : Bonus · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 8

**Étiquettes** : `could` `engagement` `front` `back`

**Description à coller** :

```
En tant qu'étudiant, je veux participer à des mini-jeux et événements afin de gagner des prix ou des points.

Critères d'acceptation : Au moins un mini-jeu fonctionnel avec gain de points.
```

#### ☐ [MM-40] Paiement en ligne (bonus)

**Epic** : Bonus · **Type** : Story · **Sprint** : Sprint 3 · **Points** : 13

**Étiquettes** : `could` `paiement` `back` `front`

**Description à coller** :

```
En tant qu'étudiant, je veux payer en ligne via un agrégateur (Mobile Money, carte) afin d'éviter les paiements sur place.

Critères d'acceptation : Intégration CinetPay ou Stripe. Fonctionnalité bonus, non obligatoire.
```

## Contrôle final

| Epic | Éléments | Points |
|---|---|---|
| Conception et socle technique | 12 | 6 |
| Comptes et accès | 5 | 18 |
| Menu et accueil | 4 | 18 |
| Commande | 7 | 32 |
| Fidélité et parrainage | 8 | 29 |
| Réclamations | 4 | 14 |
| Administration et statistiques | 4 | 18 |
| Qualité, tests et déploiement | 7 | 23 |
| Documentation et gestion de projet | 7 | 17 |
| Bonus | 3 | 24 |
| **Total** | **61** | **199** |

Le total doit être de 61 éléments dans Jira, plus les 10 epics.
