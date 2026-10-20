# API backend — Mon Miam Miam

Pour installer et utiliser l’authentification Sanctum, ouvrir Swagger ou tester les appels dans Postman, voir [Authentification Sanctum et documentation Swagger](Authentification_Sanctum_et_Swagger.md).

Préfixe local : `http://127.0.0.1:8000/api`. Toutes les données sont JSON, sauf l'envoi d'image du catalogue. Les routes privées demandent `Authorization: Bearer <jeton>` et `Accept: application/json`.

## Authentification et menu

| Méthode | Route | Accès | Résumé |
|---|---|---|---|
| POST | `/auth/inscription` | Public | Créer un étudiant; accepte `code_parrain` |
| POST | `/auth/connexion` | Public | Retourne un jeton Bearer; champs `email`, `mot_de_passe`, `se_souvenir` |
| POST | `/auth/deconnexion` | Connecté | Révoquer le jeton courant |
| POST | `/auth/mot-de-passe-oublie` | Public | Demander un lien de réinitialisation |
| GET/PATCH/DELETE | `/moi` | Connecté | Lire/modifier/anonymiser le compte; DELETE exige `mot_de_passe` |
| PUT | `/moi/mot-de-passe` | Connecté | Changer le mot de passe avec l'ancien et `nouveau_mot_de_passe_confirmation` |
| GET | `/categories`, `/menu-du-jour` | Public | Consulter le catalogue du jour |

Les erreurs de validation retournent `422` avec `message` et `errors`. Un utilisateur sans rôle autorisé reçoit `403`; sans jeton valide, `401`.

La suppression de compte est une anonymisation : elle exige de confirmer le mot de passe, neutralise les informations personnelles et révoque les jetons. Les commandes et paiements historiques sont conservés pour l'intégrité comptable.

## Commandes et paiement

| Méthode | Route | Accès | Résumé |
|---|---|---|---|
| POST | `/commandes/devis` | Étudiant | Calculer prix, promotions et utilisation des points sans créer de commande |
| POST | `/commandes` | Étudiant | Créer une commande au statut `panier`; lignes `article_id`, `quantite`; commentaire facultatif |
| GET | `/commandes` | Étudiant | Historique filtrable `etat=a_payer\|en_cours\|terminees` |
| GET | `/commandes/{id}` | Propriétaire | Détail et historique des statuts |
| POST | `/commandes/{id}/paiement` | Étudiant propriétaire | Initier avec `telephone` et `operateur=orange_money\|mtn_momo` |
| POST | `/paiements/notification` | Agrégateur | Retour serveur signé par `X-MMM-Signature` |
| POST | `/paiements/{id}/simulation` | Local/tests | Simuler `status=success\|failed` |
| POST | `/commandes/{id}/annuler` | Étudiant | Annuler selon les règles de statut |

Pour une présence sur place, envoyer `mode_service=sur_place` et `heure_arrivee=HH:MM`. Pour une livraison, envoyer `mode_service=livraison` et `adresse_livraison`. Le navigateur n'envoie jamais un prix : le serveur le recalcule depuis la base.

## Équipe, catalogue et promotions

- Personnel : `GET /staff/menu`, `PUT /staff/menu-du-jour`, `PATCH /staff/articles/{id}/disponibilite`.
- Commandes du service : `GET /staff/commandes`; actions POST `/demarrer`, `/prete`, `/cloturer`, `/notifier`, `/annuler`.
- Articles et promotions : `/gestion/articles`, `/gestion/promotions`; accès gérant/administrateur, suppression d'article réservée à l'administrateur.
- Réclamations : étudiant `GET/POST /reclamations`; personnel `GET /staff/reclamations` et POST `/{id}/reponse`; direction POST `/gestion/reclamations/{id}/decision`.

## Événements et administration

- `GET /evenements`, `GET /mini-jeux`; étudiant `POST /evenements/{id}/participer`, puis `POST /mini-jeux/{id}/question` et `POST /mini-jeux/{id}/parties` avec `session_id` et `reponse`, enfin `GET /classement`.

Une session de jeu est liée à l'étudiant et expire après cinq minutes. Le score et les points sont calculés par Laravel; le navigateur ne peut pas demander directement un nombre de points.
- Administrateur : `GET /administration/tableau-de-bord`, `/equipe`, `/restaurants/{id}/etudiants`, `/parametres-fidelite`; modification par PUT de `/parametres-fidelite`.

La réponse de paiement réussie met à jour le statut de paiement, confirme la commande, écrit les mouvements de points et traite le parrainage dans une transaction. Une même notification répétée ne crédite pas les points deux fois.

## Format des échanges et règles de sécurité

- En-têtes standards : `Accept: application/json`; pour une action privée `Authorization: Bearer <jeton>`; un corps JSON doit aussi préciser `Content-Type: application/json`.
- `POST /commandes/devis` calcule un montant sans enregistrer de commande. `POST /commandes` reçoit articles, quantités, service et commentaire, mais jamais un prix fiable : Laravel recalcule depuis le catalogue.
- `operateur` accepte `orange_money` ou `mtn_momo`; la configuration `.env` choisit `simulation` ou `aggregator`.
- `POST /paiements/notification` est une callback serveur-à-serveur et exige la signature configurée. Ne pas simuler un appel réel avec une signature absente en production.
- `POST /paiements/{id}/simulation` est destiné à la démonstration locale et tests; ne pas rendre cette route accessible en production.
- Les gardes `auth:api` et `role:...` s'appliquent côté Laravel. Cacher un bouton dans React ne constitue pas un contrôle d'accès.

Pour la liste précise des paramètres des Form Requests et le cycle des statuts, consulter le [guide technique Laravel/React/API/paiements](../installation/Guide_Technique_Laravel_React_API_Paiements.md). Le projet ne déclare actuellement pas de vues SQL ou de triggers; les fonctions de présentation sont des endpoints JSON et les règles de points sont dans les services métier.
