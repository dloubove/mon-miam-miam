# Guide d'installation et de démonstration du backend

## Documentation complémentaire

- [Inventaire des migrations PostgreSQL et contraintes](../architecture/Migrations_PostgreSQL_Mon_Miam_Miam.md) : nombre, tables, relations, vérification, domaines, vues et triggers.
- [Démarrer PostgreSQL et connecter PHP/Laravel](Guide_Connexion_PHP_PostgreSQL.md) : service Windows, `pdo_pgsql`, `.env`, migrations et affichage des tables dans pgAdmin/DataGrip; contient également la clarification `User` / `Utilisateur`.
- [Guide technique détaillé Laravel, React, API et paiements](Guide_Technique_Laravel_React_API_Paiements.md) : fonctions utilisées, dépendances, exemples et commandes PowerShell.
- [Référence des endpoints API](../api/API_Backend_Mon_Miam_Miam.md).
- [Architecture MVC/POO/Inertia](../architecture/Backend_Mon_Miam_Miam.md).

Le dépôt contient 31 migrations au total : 3 migrations génériques Laravel, 25 migrations métier initiales et 3 migrations métier complémentaires. Consultez `migrate:status` pour savoir lesquelles ont réellement été exécutées dans votre base locale.

## Pré-requis

- PHP 8.2 ou plus, avec `pdo_pgsql` (et `pgsql`), `mbstring`, `xml`, `curl`, `fileinfo`, `openssl` et `zip` activés.
- Composer 2, Node.js/NPM et PostgreSQL 14 ou plus.

## Installation locale

1. Créer deux bases PostgreSQL : `mon_miam_miam` et `mon_miam_miam_test`.
2. Dans `backend/`, lancer `composer install` (le fichier `composer.lock` verrouille les versions).
3. Copier `.env.example` en `.env`, renseigner les accès PostgreSQL et laisser `MOBILE_MONEY_MODE=simulation`.
4. Lancer `php artisan key:generate`, puis `php artisan migrate --seed`.
5. Lancer `php artisan storage:link` pour les photos.
6. Dans `frontend/`, lancer `npm ci`, renseigner `VITE_API_URL=http://127.0.0.1:8000/api` dans `.env`, puis `npm run dev`.
7. Dans `backend/`, lancer `php artisan serve`; ouvrir l'adresse Laravel retournée. La page React/Inertia appelle alors l'API.

Les seeders créent les comptes de test en environnement local. Les huit comptes partagent le mot de passe `Demo1234`; ne pas réutiliser ces comptes en production.

| Profil | Nom | Email de connexion |
|---|---|---|
| Étudiant | Dan Loubove | `etudiant@ucac-icam.com` |
| Étudiant | Nathan Bella | `nathan@ucac-icam.com` |
| Étudiant | Abdul Kadir (filleul de Dan) | `abdul@ucac-icam.com` |
| Employé | CHRISTE | `christe@zeduc.com` |
| Employé | Paul Zogo | `paul@zeduc.com` |
| Employée | Mireille Ekotto | `mireille@zeduc.com` |
| Gérante | EMMA | `emma@zeduc.com` |
| Administrateur/propriétaire représenté | Junior Mbongue | `admin@zeduc.com` |

Dans une nouvelle base locale, `php artisan migrate --seed` lance les seeders automatiquement. Dans une base déjà migrée, `php artisan db:seed` (depuis `APP_ENV=local`) recharge les références, les profils de démo et, si aucune commande n'existe, les commandes de démonstration. Après une ancienne version du jeu de données, des comptes de démonstration historiques peuvent rester dans la base; ne lancez pas `migrate:fresh` si vous devez garder les données.

## Vérifier les migrations et données

- `php artisan migrate:status` affiche les migrations effectivement exécutées.
- Dans pgAdmin ou DataGrip, sélectionner la base `mon_miam_miam`, puis vérifier `public.migrations` et les tables `utilisateur`, `restaurant`, `article`, `commande`, `paiement`, `mouvement_points`.
- `php artisan db:seed --class=ReferenceSeeder` charge les références; `DemoSeeder` crée des comptes/plats de démonstration hors production.

## Lancer la suite de tests sans toucher à la base de développement

`phpunit.xml` cible `mon_miam_miam_test`, pas `mon_miam_miam`. Vérifier que cette base existe et que l'utilisateur PostgreSQL y a accès, puis lancer `php artisan test`. Ne changez pas `DB_DATABASE` de test vers votre base de démo : `RefreshDatabase` réinitialise les tables pendant les tests.

Les tests positifs vérifient les parcours autorisés; les tests négatifs vérifient les rôles, données invalides, transitions interdites et duplications. PHPUnit affiche chaque méthode exécutée et le total `PASS/FAIL`; le rapport détaillé peut être exporté avec `php artisan test --log-junit storage/logs/tests.xml`.

## Parcours de démonstration du paiement simulé

1. Se connecter comme étudiant avec `etudiant@ucac-icam.com` / `Demo1234`.
2. Ajouter un article du menu et valider une commande.
3. Choisir Orange Money ou MTN MoMo; en mode simulation, le frontend termine le paiement de test.
4. Vérifier le statut de la commande, la ligne dans `paiement` et le mouvement `gain_commande` dans `mouvement_points`.
5. Rejouer une notification de paiement ne doit pas créer un deuxième mouvement de gain.

## Limite du fournisseur Mobile Money

L'application ne peut pas encaisser de l'argent réel avec des clés fictives. Pour le pilote réel, demander à l'agrégateur son sandbox, ses identifiants, son URL, son contrat de notification et son algorithme de signature; les placer dans `.env` et adapter `PaiementAgregateurGateway` à son API officielle. La simulation reste l'environnement recommandé pour la soutenance.
