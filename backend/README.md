# Backend — Mon Miam Miam (ZeDuc@Space)

API Laravel 12 + PostgreSQL pour l'application de restauration étudiante.

## Prérequis
PHP ≥ 8.2 (extensions `pgsql`, `mbstring`, `xml`, `curl`, `zip`), Composer 2, PostgreSQL ≥ 14.

## Installation
```bash
cd backend
composer install
cp .env.example .env            # puis ajuster DB_USERNAME / DB_PASSWORD
php artisan key:generate

# créer les bases (une pour le dev, une pour les tests)
createdb -U postgres mon_miam_miam
createdb -U postgres mon_miam_miam_test

php artisan migrate --seed      # tables + données de référence + démo
php artisan storage:link        # rend les photos accessibles sur /storage (à faire une seule fois)
php artisan serve               # http://localhost:8000
php artisan test                # tests (utilisent mon_miam_miam_test)
```

## Étape 1 — Base de données (terminée)
- 22 migrations Laravel, une par table, dans l'ordre des dépendances (`database/migrations/2026_10_18_*`).
  Les tables et colonnes gardent les noms du MCD/MLD (`utilisateur`, `id_etudiant`, `solde_points`…).
- Les « domaines » du script SQL d'origine sont remplacés par des contraintes `CHECK`
  (`app/Support/DbConstraints.php`) : statuts autorisés, montants positifs, cohérence des mouvements de points,
  commande sur place → heure d'arrivée / livraison → adresse, etc. Deux index uniques partiels garantissent
  un seul gain et une seule utilisation de points par commande.
- `ReferenceSeeder` (sûr en production) : rôles, catégories, restaurant, règles de fidélité, mini-jeux.
- `DemoSeeder` (jamais en production) : comptes, 9 articles, menu du jour, 2 événements.

### Comptes de démonstration (mot de passe : `Demo1234`)
| Rôle | Email |
|---|---|
| Étudiant | etudiant@ucac-icam.com |
| Employé | paul@zeduc.com |
| Gérant | marc@zeduc.com |
| Administrateur | admin@zeduc.com |

### Règles métier à réécrire côté PHP (anciens triggers SQL)
Le schéma Laravel ne contient pas les triggers du script `MMM_inv.sql`. Ces règles seront portées dans des services
transactionnels aux étapes suivantes :

| Règle | Étape |
|---|---|
| ~~Solde de points = somme des mouvements, modifiable uniquement via un mouvement~~ ✅ | 4 |
| ~~Mouvements de points immuables~~ ✅ · parties de mini-jeu immuables | 4 / 7 |
| ~~Prix de l'article figé dans la commande (`contenir.prix_applique`)~~ ✅ | 4 |
| ~~Lignes de commande non modifiables hors panier~~ ✅ | 4 |
| ~~Débit des points quand la commande est confirmée~~ ✅ | 4 |
| Gain de points au paiement réussi | 5 |
| Récompense de parrainage à la première commande payée | 5 |
| ~~Un utilisateur est soit étudiant, soit membre du personnel~~ ✅ | 2 |
| ~~Usage unique et expiration du jeton de mot de passe~~ ✅ | 2 |
| Crédit des points d'une partie de mini-jeu | 7 |

## Étape 2 — Authentification (terminée)

### Principe
Le frontend s'identifie avec un **jeton** (en-tête `Authorization: Bearer <jeton>`), sans cookie ni session.
À la connexion, l'API génère un jeton aléatoire de 64 caractères, le renvoie **une seule fois**, et ne garde en base
que son **empreinte SHA-256** (table `jeton_acces`). Il expire après 1 jour (30 jours avec « se souvenir de moi »)
et se révoque à la déconnexion, au changement de mot de passe ou à la désactivation du compte.

> Sanctum n'a pas pu être installé dans l'environnement de développement de cette étape (accès à Packagist bloqué).
> Le système de jetons maison (`JetonService`) reprend la même conception ; on pourra migrer vers Sanctum plus tard
> sans changer le contrat de l'API.

### Routes
| Méthode | URL | Accès | Rôle |
|---|---|---|---|
| POST | `/api/auth/inscription` | public (10/min/IP) | Crée un compte étudiant (+ code parrain facultatif) et renvoie un jeton |
| POST | `/api/auth/connexion` | public (5/min/email+IP) | Email + mot de passe → jeton (`se_souvenir` facultatif) |
| POST | `/api/auth/deconnexion` | connecté | Supprime le jeton utilisé |
| POST | `/api/auth/mot-de-passe-oublie` | public | Envoie le lien par email (réponse identique si l'email est inconnu) |
| POST | `/api/auth/reinitialiser-mot-de-passe` | public | Nouveau mot de passe via le lien (usage unique, 1 h) |
| GET | `/api/moi` | connecté | Profil, rôle, points, code de parrainage |
| PATCH | `/api/moi` | connecté | Modifie nom, email, téléphone, lieu de livraison |
| PUT | `/api/moi/mot-de-passe` | connecté | Change le mot de passe (déconnecte les autres appareils) |
| DELETE | `/api/moi` | connecté | Supprime le compte (anonymisation : l'historique des commandes est conservé) |

Réservez une route à certains rôles avec `->middleware(['auth:api', 'role:gerant,administrateur'])`.

### Exemple
```bash
curl -X POST http://localhost:8000/api/auth/connexion \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"etudiant@ucac-icam.com","mot_de_passe":"Demo1234"}'
# → { "utilisateur": { "role": "etudiant", "etudiant": { "solde_points": 45, ... } }, "jeton": "...", "expire_le": "..." }

curl http://localhost:8000/api/moi -H 'Authorization: Bearer <jeton>'
```

### Sécurité mise en place
- Mots de passe hachés (bcrypt), règle : 8 caractères, 1 majuscule, 1 chiffre.
- Message d'erreur unique « Email ou mot de passe incorrect » + vérification de hash même si l'email n'existe pas (pas de fuite de comptes).
- Limiteurs de débit contre la force brute ; CORS limité à `FRONTEND_URL`.
- Jetons et liens de réinitialisation stockés sous forme d'empreinte uniquement ; rôle et points jamais modifiables via les formulaires.
- Règle « étudiant OU personnel » vérifiée dans les modèles `Etudiant` et `Personnel`.

### Correspondance avec le frontend
- L'API a un seul champ `nom` (pas `prenom`/`nom`) : le frontend enverra le nom complet.
- Le lieu de livraison habituel et la résidence tiennent dans le champ `localisation`.
- Réponses d'erreur de validation : `422 { "message": ..., "errors": { "champ": ["..."] } }`.

## Étape 3 — Catalogue et menu du jour (terminée)

### Notions
- **Catalogue** = tous les articles du restaurant (table `article`). **Menu du jour** = ceux proposés aujourd'hui (table `composer`).
- **Rupture** = `disponible = false` : l'article reste affiché mais n'est plus commandable.
- **Catégories** : plat, snack, boisson, dessert. L'onglet « Plats » du frontend = tout sauf `boisson`.
- **Promotions** : table de liaison `concerner` (ajoutée à cette étape). Le prix remisé est **calculé** (prix × (1 − taux/100), arrondi au franc) et jamais stocké ; avec plusieurs promotions, la plus forte s'applique. Les filtres et tris de prix portent sur le prix réellement payé.

### Routes
| Méthode | URL | Accès | Rôle |
|---|---|---|---|
| GET | `/api/categories` | public | Liste des catégories |
| GET | `/api/menu-du-jour` | public | Articles d'aujourd'hui, filtres + pagination |
| GET | `/api/staff/menu` | employé, gérant, admin | Tous les articles avec `dans_menu` et `disponible` |
| PUT | `/api/staff/menu-du-jour` | employé, gérant, admin | Compose le menu : `{"articles":[1,2,3]}` (liste vide = aucun plat) |
| PATCH | `/api/staff/articles/{id}/disponibilite` | employé, gérant, admin | `{"disponible": false}` = rupture |
| GET | `/api/gestion/articles` | gérant, admin | Catalogue complet (mêmes filtres) |
| POST | `/api/gestion/articles` | gérant, admin | Crée un article (multipart avec photo) |
| PATCH | `/api/gestion/articles/{id}` | gérant, admin | Modifie les champs envoyés |
| DELETE | `/api/gestion/articles/{id}` | **admin seulement** | Refusé (409) si déjà commandé |
| GET/POST | `/api/gestion/promotions` | gérant, admin | Liste / crée une promotion |
| PATCH/DELETE | `/api/gestion/promotions/{id}` | gérant, admin | Modifie / supprime (409 si déjà utilisée) |

### Filtres de `/api/menu-du-jour` et `/api/gestion/articles`
`type` (plats\|boissons) · `categorie` (nom ou id) · `q` (recherche nom/description) · `prix_min` · `prix_max` ·
`disponible=1` · `promo=1` · `tri` (defaut\|prix_asc\|prix_desc\|nom) · `par_page` (1 à 50) · `page`.

Exemples : accueil → `/api/menu-du-jour?type=plats&par_page=3` ; « Voir plus » →
`/api/menu-du-jour?type=plats&q=poulet&prix_max=3000&tri=prix_asc&disponible=1&page=1`.

### Format des réponses
- Liste **paginée** : `{ "data": [...], "links": {...}, "meta": { "total": 6, "current_page": 1, ... } }`.
- Liste **non paginée** (catégories, promotions) : tableau JSON direct.
- Un article : `{ id, nom, description, categorie:{id,libelle}, prix, prix_normal, promotion:{id,titre,taux}|null, image_url, disponible, dans_menu? }`.
  `prix` est déjà le prix à payer ; `prix_normal` (ancien prix, à barrer) n'existe que s'il y a une promotion.

### Photos
Envoyées en `multipart/form-data` (champ `image`, JPG/PNG/WebP, 2 Mo max), stockées dans `storage/app/public/articles`.
Pour **modifier** un article avec une nouvelle photo, le navigateur envoie un `POST` avec un champ `_method=PATCH`
(PHP ne lit pas les fichiers d'une vraie requête PATCH). Les booléens s'envoient en `1` / `0`.
Les 4 photos de démo viennent de `database/seeders/images/` ; remplacez-les par vos originaux.

## Étape 4 — Commandes (terminée)

### Cycle de vie d'une commande
```
panier ──confirmer──▶ en_attente ──▶ en_preparation ──▶ prete ──▶ servie (sur place) | livree (livraison)
   └──────────────────────┴──────────────────┴──────────────┴──▶ annulee
```
| Colonne du tableau du personnel | Statut en base | Bouton |
|---|---|---|
| (invisible : en attente de paiement) | `panier` | — |
| À préparer | `en_attente` | « Lancer la préparation » |
| En cuisine | `en_preparation` | « Marquer comme PRÊT » |
| Prêt | `prete` | « Notifier » / « Clôturer » |
| (terminée) | `servie` ou `livree` | — |

- **Création** (`POST /api/commandes`) : la commande naît au statut `panier` avec ses lignes au **prix figé** (promotion comprise).
  Aucun point n'est débité.
- **Confirmation** (`panier` → `en_attente`) : les points sont contrôlés (seuil, multiple de la tranche, solde suffisant,
  réduction ≤ montant) puis **débités** ; les totaux sont figés. Elle rend la commande visible du personnel.
  *À l'étape 5, c'est le paiement réussi qui déclenchera cette confirmation.* En attendant, `POST /api/commandes/{id}/confirmer`
  la déclenche à la main, **uniquement si `COMMANDES_CONFIRMATION_DIRECTE=true`** dans le `.env` (test local ; à laisser à `false`
  en production, sinon on pourrait commander sans payer).
- **Réduction par points** : l'étudiant utilise le **maximum** possible : autant de tranches de 15 points que son solde le permet,
  sans dépasser le montant de la commande (45 points → 3 000 FCFA). Une commande peut être couverte à 100 % (total 0).
- **Annulation** : l'étudiant peut annuler tant que la commande est `panier` ou `en_attente` ; le personnel jusqu'à `prete`.
  Les points dépensés sont rendus par un mouvement correctif (`ajustement`), jamais en modifiant le grand livre.
- **Journal** (`historique_commande`, table ajoutée à cette étape) : chaque changement de statut avec l'heure et l'auteur.
  Il alimente la frise de suivi de l'étudiant.

### Routes
| Méthode | URL | Accès | Rôle |
|---|---|---|---|
| POST | `/api/commandes/devis` | étudiant | Prix exact (promotions, points, points à gagner) sans rien enregistrer |
| POST | `/api/commandes` | étudiant | Passe la commande (statut `panier`) |
| GET | `/api/commandes?etat=en_cours\|terminees\|a_payer` | étudiant | Mes commandes (paginé) |
| GET | `/api/commandes/{id}` | étudiant | Détail + frise de suivi (404 si ce n'est pas la mienne) |
| POST | `/api/commandes/{id}/confirmer` | étudiant | Confirme sans paiement (mode test uniquement) |
| POST | `/api/commandes/{id}/annuler` | étudiant | Annule (tant que pas en préparation) |
| GET | `/api/staff/commandes` | personnel | Les 3 colonnes : `a_preparer`, `en_cuisine`, `pret` |
| POST | `/api/staff/commandes/{id}/demarrer` | personnel | Lancer la préparation |
| POST | `/api/staff/commandes/{id}/prete` | personnel | Marquer comme prêt |
| POST | `/api/staff/commandes/{id}/cloturer` | personnel | Clôturer (`servie` ou `livree` selon le mode) |
| POST | `/api/staff/commandes/{id}/notifier` | personnel | Email « votre commande est prête » |
| POST | `/api/staff/commandes/{id}/annuler` | personnel | Annule (rupture d'ingrédient…) |
| POST | `/api/staff/commandes/{id}/encaisser-especes` | personnel | Confirme l’encaissement des espèces et crédite la fidélité |

### Paiements Mobile Money et espèces

- `POST /api/commandes/{id}/paiement` crée une session Mobile Money. En mode CinetPay, React redirige vers l’URL sécurisée fournie par Checkout v2 ; le callback signé est vérifié côté serveur avant la confirmation.
- `POST /api/commandes/{id}/paiement-especes` confirme la commande pour la cuisine tout en gardant le paiement en attente.
- Le personnel passe ensuite par `/api/staff/commandes/{id}/encaisser-especes` pour confirmer le paiement reçu.
- `MOBILE_MONEY_MODE=simulation` garde le mode de démonstration ; pour CinetPay, utiliser `MOBILE_MONEY_MODE=cinetpay` et configurer les identifiants dans `.env`.
- Les remboursements CinetPay ne sont pas automatisés dans cette intégration ; voir [la documentation détaillée](../docs/03-technique/api/Paiements_CinetPay_Especes.md).

Corps de `POST /api/commandes` et `/devis` :
```json
{ "lignes": [ {"article_id": 2, "quantite": 1}, {"article_id": 7, "quantite": 2} ],
  "mode_service": "sur_place",          // ou "livraison"
  "heure_arrivee": "12:45",              // obligatoire sur place
  "adresse_livraison": "Résidence …",    // facultatif en livraison : par défaut, le lieu habituel du profil
  "commentaire": "Sans piment",
  "utiliser_points": true }
```
Les articles doivent être **au menu d'aujourd'hui et en stock**, sinon `422` avec un message par ligne.
Une action impossible renvoie `409 { "message": "Action impossible : la commande est « Livrée »." }`.

### Écarts avec le script SQL d'origine (à connaître)
- `contenir.prix_applique` = prix **du jour, promotion d'article comprise** (le script d'origine copiait le prix de base). Le prix payé reste ainsi lisible dans la commande.
- `commande.id_promotion` (promotion globale sur la commande) n'est pas utilisé : les promotions de ce projet sont par article (étape 3).
- Le **livreur** (nom, note, appel) du frontend n'existe pas dans la base : il n'est pas fourni par l'API.
- Le panier est conservé **côté frontend** jusqu'à la commande ; le serveur ne stocke pas de panier modifiable.
- Les **points expirent** (`date_expiration_points`, 12 mois) : la tâche d'expiration reste à écrire (étape 8).
- Les mouvements de points sont immuables et les lignes d'une commande confirmée aussi, mais comme règles de l'application (modèles Eloquent), plus par des triggers.

### Données de démonstration
En environnement `local` uniquement, `php artisan db:seed` crée aussi 6 commandes (une par colonne du tableau, deux
dans l'historique de Dan) et deux étudiants (`sophie@ucac-icam.com`, `arthur@ucac-icam.com`, mot de passe `Demo1234`).
Le fuseau de la session PostgreSQL suit maintenant `APP_TIMEZONE` (`config/database.php`).

## Plan
1. Base de données ✅ · 2. Authentification ✅ · 3. Catalogue & menu du jour ✅ · 4. Commandes ✅ · 5. Paiement ·
6. Réclamations · 7. Événements & mini-jeux · 8. Administration · 9. Tests, sécurité, branchement du frontend.
