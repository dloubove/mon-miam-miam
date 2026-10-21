# Authentification Sanctum et documentation Swagger

Ce document décrit le fonctionnement actuel de l’authentification API de Mon Miam Miam, le stockage des jetons sous PostgreSQL, les routes documentées et la manière de tester l’API avec Swagger ou Postman.

## 1. Choix d’authentification

Le frontend React consomme l’API Laravel en envoyant un jeton dans l’en-tête HTTP `Authorization: Bearer <jeton>`. Laravel Sanctum crée des jetons d’accès personnels. Le navigateur n’a pas besoin que ces jetons soient enregistrés dans une table de cookies. Le stockage côté frontend reste celui déjà en place dans le projet.

La table `utilisateur` reste la table des quatre types de comptes. Le modèle `App\Models\Utilisateur` utilise le trait `Laravel\Sanctum\HasApiTokens`; Sanctum connaît donc la clé primaire non standard `id_utilisateur` à travers la relation polymorphe.

Après connexion, la réponse contient le jeton Sanctum :

```json
{
  "utilisateur": { "id_utilisateur": 1, "nom": "Dan Loubove", "role": "etudiant" },
  "jeton": "1|jeton-secret-retourné-une-seule-fois",
  "type": "Bearer",
  "expire_le": "2026-10-21T12:00:00+00:00"
}
```

L’inscription ne connecte plus automatiquement : elle renvoie `verification_requise`, l’email et un message de confirmation. La valeur du jeton en clair n’est renvoyée qu’après connexion. PostgreSQL conserve son empreinte SHA-256 dans `personal_access_tokens.token`. Le frontend envoie ensuite :

```http
Authorization: Bearer 1|jeton-secret-retourné-une-seule-fois
Accept: application/json
```

Un jeton normal expire après 1 jour. `se_souvenir: true` donne une durée de 30 jours. La date est enregistrée dans `expires_at` et contrôlée par Sanctum à chaque requête.

## 2. Cycle de vie et sécurité

- `POST /api/auth/inscription` crée un compte étudiant et son profil, puis envoie un lien signé valable 24 heures. Aucun jeton n’est créé à l’inscription.
- `GET /api/auth/verification-email/{id}/{hash}` vérifie le lien signé et enregistre `utilisateur.email_verified_at`. Le navigateur est redirigé vers la page de connexion. La confirmation est demandée une seule fois ; les connexions suivantes ne redemandent pas de code.
- `POST /api/auth/renvoyer-verification-email` permet de renvoyer le lien. La réponse ne révèle pas si l’adresse existe.
- `POST /api/auth/connexion` vérifie l’email, le mot de passe, l’état actif et la confirmation de l’adresse avant de créer un jeton.
- `POST /api/auth/deconnexion` supprime uniquement le jeton envoyé dans cette requête.
- Le changement de mot de passe révoque les jetons des autres appareils, tout en gardant celui qui a effectué le changement.
- La réinitialisation du mot de passe et l’anonymisation du compte révoquent tous les jetons.
- Les routes protégées utilisent `auth:sanctum`, puis `compte.actif` et les règles `role` selon le cas.
- Les erreurs d’accès à l’API sont retournées en JSON. Une route protégée sans jeton, avec jeton invalide, expiré ou révoqué retourne `401`.
- Les rôles ne sont pas codés dans le jeton : Laravel les relit depuis le profil en base, ce qui permet de les modifier sans conserver de vieux privilèges dans un jeton.
- Le champ nullable `utilisateur.email_verified_at` est ajouté par la migration `add_email_verified_at_to_utilisateur`. Modifier l’adresse dans le profil remet ce champ à `NULL` et envoie une confirmation à la nouvelle adresse.

### Courriel en développement

Dans le `.env` de développement, `MAIL_MAILER=log` écrit le message au lieu de l’envoyer à une vraie boîte. Après une inscription, chercher `Confirmez votre adresse email` dans `backend/storage/logs/laravel.log`, puis ouvrir le lien affiché. Pour l’envoi réel, configurer un transport SMTP dans `.env` (`MAIL_MAILER=smtp`, hôte, port, identifiant, mot de passe et expéditeur) et vider le cache de configuration avec `php artisan config:clear`.

Les comptes chargés par `DemoSeeder` sont marqués confirmés afin de rester utilisables immédiatement en démonstration.

La migration `create_personal_access_tokens_table` crée la table Sanctum. La table historique `jeton_acces` et les anciennes classes associées sont conservées pour ne pas casser les bases déjà migrées, mais le nouveau code d’authentification ne les utilise plus. Les anciens jetons de cette table ne sont pas compatibles : les utilisateurs doivent se reconnecter après le déploiement.

## 3. Mise en place locale

Depuis le dossier `backend` :

```bash
composer install
php artisan package:discover
php artisan migrate
```

`php artisan migrate` ajoute `personal_access_tokens` à la base définie dans `.env`. Il ne faut pas utiliser `migrate:fresh` sur une base qui contient des données, car cette commande supprime les tables avant de les recréer.

Si `composer.json` change, actualiser le verrou puis installer les dépendances :

```bash
composer update --lock
composer install
```

Les versions prévues dans `composer.json` sont `laravel/sanctum ^4.3` et `darkaonline/l5-swagger ^11.2`, compatibles avec Laravel 12.

## 4. Documentation OpenAPI avec L5-Swagger

Le contrat OpenAPI est défini dans `backend/app/OpenApi/ApiSpecification.php`. Il documente les opérations réellement exposées dans `routes/api.php`, leurs méthodes HTTP, chemins, rôles d’accès, paramètres de chemin et réponses principales. La définition du serveur utilise `/api` ; Swagger affiche donc les URL complètes à partir de l’adresse du backend.

Générer le fichier OpenAPI après avoir modifié les annotations :

```bash
php artisan l5-swagger:generate
```

Puis démarrer Laravel et ouvrir :

```text
http://localhost:8000/api/documentation
```

Le fichier JSON généré se trouve dans `backend/storage/api-docs/api-docs.json`. Swagger UI présente les opérations par domaine. Pour tester une route protégée dans Swagger :

1. appeler `POST /auth/connexion` (ou l’inscription) pour obtenir `jeton` ;
2. cliquer sur **Authorize** ;
3. saisir le jeton seul, sans ajouter le texte `Bearer` ;
4. essayer une opération protégée correspondant au rôle du compte.

La documentation Swagger est accessible sans connexion dans cette configuration de développement. En production, protéger son accès ou le désactiver selon la politique de déploiement. La publication de cette documentation ne remplace jamais le contrôle des autorisations sur les routes.

## 5. Vérifier avec Postman

1. Envoyer `POST http://localhost:8000/api/auth/connexion`, sélectionner **Body → raw → JSON** et fournir `email` et `mot_de_passe`.
2. Copier le champ `jeton` de la réponse.
3. Pour les requêtes protégées, choisir **Authorization → Bearer Token**, coller le jeton et garder `Accept: application/json`.
4. Tester `GET /api/moi` pour voir le compte authentifié, puis `POST /api/auth/deconnexion`.
5. Réutiliser le jeton révoqué sur `GET /api/moi` : la réponse attendue est `401`.

Exemple connexion :

```http
POST http://localhost:8000/api/auth/connexion
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "dan@example.cm",
  "mot_de_passe": "votre-mot-de-passe-de-test",
  "se_souvenir": false
}
```

## 6. Domaines d’API couverts

| Domaine | Exemples de routes | Accès |
|---|---|---|
| Authentification | inscription, connexion, déconnexion, réinitialisation | Public, sauf déconnexion |
| Profil | `GET/PATCH /moi`, changement de mot de passe, suppression | Compte connecté |
| Catalogue | catégories, menu du jour | Public |
| Commandes | devis, création, historique, détail, confirmation, annulation | Étudiant connecté |
| Paiements | initiation, notification agrégateur, simulation | Étudiant ou callback agrégateur |
| Réclamations | création étudiant, traitement personnel, décision gérant | Selon rôle |
| Fidélité et jeux | événements, mini-jeux, participation, classement | Public ou étudiant connecté |
| Administration | tableau de bord, équipe, liste étudiants/restaurant, paramètres | Administrateur |
| Personnel | menu et suivi du service | Employé, gérant ou administrateur |
| Gestion | articles et promotions | Gérant ou administrateur |

Les corps JSON détaillés et leurs règles de validation restent définis par les Form Requests et contrôleurs Laravel ; les réponses exactes peuvent donc inclure des champs propres au rôle et à l’état métier.
