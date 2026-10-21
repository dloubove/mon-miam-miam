# Scripts SQL PostgreSQL de Mon Miam Miam

## Objet et portée

Cette documentation accompagne les scripts de création de la base Mon Miam Miam, version 2.0. Le modèle suit le schéma relationnel de la spécification : 22 tables, avec MINI_JEU et JOUER. Les règles qui se vérifient dans une même ligne sont des contraintes PostgreSQL ; celles qui dépendent de plusieurs tables sont implémentées par des déclencheurs dans `MMM_inv.sql`.

Les fichiers sont exécutés dans l’ordre ci-dessous depuis `psql` ou un client PostgreSQL. Les scripts supposent que l’utilisateur dispose du droit de créer un schéma et des objets.

## Ordre d’exécution

1. `MMM_ini.sql` crée le schéma `mmm`. Il commence par supprimer ce schéma s’il existe : toutes les données qu’il contient sont alors perdues.
2. `MMM_cre.sql` crée les domaines, les 22 tables, les clés, les contraintes déclaratives et les index.
3. `MMM_inv.sql` crée les fonctions et déclencheurs qui appliquent les invariants métier entre plusieurs tables.
4. `MMM_ref.sql` insère les rôles, catégories, mini-jeux initiaux, le restaurant de démonstration et les paramètres de fidélité.
5. `MMM_req.sql` crée les vues de suivi des soldes, ventes, clients, mini-jeux et parrainages.
6. `MMM_jdd.sql` ajoute un étudiant et un article de démonstration.
7. `MMM_test-pos.sql` et `MMM_test-neg.sql` montrent des scénarios acceptés et rejetés. Ils ne remplacent pas une recette applicative complète.

Exemple :

```text
psql -d nom_base -f MMM_ini.sql
psql -d nom_base -f MMM_cre.sql
psql -d nom_base -f MMM_inv.sql
psql -d nom_base -f MMM_ref.sql
psql -d nom_base -f MMM_req.sql
psql -d nom_base -f MMM_jdd.sql
```

## Description des scripts

### `MMM_ini.sql`

Prépare l’espace de noms PostgreSQL `mmm`. La suppression en cascade rend ce script adapté à une installation neuve ou à une réinitialisation de développement. Ne pas le relancer sur une base contenant des données à conserver.

### `MMM_cre.sql`

Déclare des domaines pour les identifiants, noms, coordonnées, montants, dates et codes fermés. Il crée ensuite les tables des comptes et rôles, restaurants et menus, commandes et paiements, fidélité et animation, réclamations, jetons de récupération et consentements. Les sous-types ETUDIANT et PERSONNEL reprennent la clé de UTILISATEUR. Les associations plusieurs-à-plusieurs sont représentées par COMPOSER, CONTENIR, PARTICIPER et JOUER.

Les prix d’articles et montants stockés sont en FCFA entiers. Les clés étrangères utilisent `ON DELETE RESTRICT`, sauf les lignes dépendantes d’un menu ou d’une commande, supprimées en cascade via COMPOSER et CONTENIR.

### `MMM_inv.sql`

Applique les règles qui nécessitent une lecture de plusieurs lignes :

- Un compte doit avoir exactement un profil, étudiant ou personnel, et jamais les deux.
- Une commande qui quitte le statut panier doit contenir un article.
- Le prix de CONTENIR est figé à partir du prix courant de l’article lors de l’écriture de la ligne.
- Les points sont contrôlés, débités à la confirmation, et convertis en réduction selon les paramètres.
- Les paiements réussis créditent les points de commande une seule fois lorsque le montant dû est entièrement réglé.
- Le mouvement de points est le grand livre : chaque mouvement met à jour le solde mémorisé et ne peut plus être modifié ou supprimé.
- La récompense de parrainage est attribuée une fois au règlement complet de la première commande du filleul.
- Les points arrivés à échéance sont expirés par des mouvements négatifs, sans effacer l’historique.
- Un jeton ne peut pas être consommé après son expiration ni réactivé après usage.
- Une partie doit concerner un mini-jeu actif et respecter le plafond. Les points remportés créent un mouvement `gain_mini_jeu`.

Les vérifications d’état de commande sont différées jusqu’à la fin de la transaction afin de permettre d’insérer d’abord la commande, puis ses articles.

### `MMM_ref.sql`

Insère un ensemble initial de rôles, catégories et mini-jeux, puis un restaurant fictif de départ. Les valeurs de fidélité proposées par le cahier des charges sont configurées : 1 point par 1 000 FCFA payés, 1 000 FCFA de remise pour une tranche de 15 points, minimum de 15 points et expiration après 12 mois. Le script évite les doublons lors de réexécutions pour les référentiels uniques.

### `MMM_req.sql`

Crée des vues en lecture :

- `v_solde_points` compare le solde mémorisé au total calculé des mouvements.
- `v_ventes_journalieres` agrège les paiements réussis par restaurant et par jour.
- `v_top_etudiants` expose le solde et le nombre de commandes terminées.
- `v_classement_mini_jeux` agrège parties, meilleur score et points remportés.
- `referrals` expose le lien parrain/filleul sans ajouter une table au modèle.

### `MMM_jdd.sql`

Crée un compte étudiant de démonstration et un article. Le mot de passe est un marqueur fictif : il faut le remplacer par un vrai hash produit par l’application avant toute utilisation réelle.

### Scripts de démonstration des tests

`MMM_test-pos.sql` insère une commande avec article et une partie de mini-jeu valide, puis annule les données avec ROLLBACK. `MMM_test-neg.sql` vérifie que le plafond de points et le prix strictement positif sont respectés. Les tests négatifs doivent afficher une notice `OK` pour chaque opération rejetée.

## Hypothèses et fonctionnement des points

- À la confirmation, les points utilisables doivent atteindre le seuil minimal et être un multiple de la tranche de remise. Par défaut, 15 points donnent 1 000 FCFA.
- Le montant total dû est recalculé depuis les lignes de commande, après promotion active puis remise en points.
- Les gains de commande sont crédités lorsque le cumul des paiements `reussi` atteint le total dû. Le gain vaut 1 point par tranche complète de 1 000 FCFA payés.
- `traiter_expiration_points()` doit être appelé quotidiennement par un ordonnanceur applicatif ou PostgreSQL. L’expiration crée des mouvements négatifs et conserve l’historique.
- La récompense de parrainage est versée au règlement complet de la première commande du filleul, selon `points_parrainage` sur le profil du filleul.
- Les gains d’événement et les corrections manuelles doivent être ajoutés sous forme de mouvements. Le déclencheur du grand livre applique leur effet au solde.
- MINI_JEU et JOUER n’ont pas de restaurant dans le modèle. La durée d’expiration des gains de mini-jeu est donc lue sur le premier restaurant. Si les programmes de fidélité varient par restaurant, le modèle devra porter ce rattachement.
- La base vérifie l’expiration d’un jeton quand il est marqué comme consommé ; l’application doit vérifier son hash et son propriétaire dans une transaction.
- La création du compte et de son sous-type doit se faire dans une même transaction. Le format du mot de passe avant hachage relève de l’application ; seul le hash est conservé.

## Vérification rapide après installation

```sql
SET search_path TO mmm, public;
SELECT table_name FROM information_schema.tables WHERE table_schema='mmm' ORDER BY table_name;
SELECT * FROM v_solde_points;
SELECT * FROM v_ventes_journalieres;
```
