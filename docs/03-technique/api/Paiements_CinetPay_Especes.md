# Paiements CinetPay et espèces

Ce document décrit le flux ajouté au backend Laravel et au panier React. Le mode local de simulation reste disponible ; aucun paiement réel ne peut fonctionner sans les identifiants marchands CinetPay.

## 1. Configuration CinetPay

Dans `backend/.env`, renseigner les identifiants obtenus dans l’espace marchand CinetPay. Ne jamais les placer dans le frontend, dans Git ou dans une capture d’écran :

```dotenv
MOBILE_MONEY_MODE=cinetpay
MOBILE_MONEY_CURRENCY=XAF
CINETPAY_API_KEY=...
CINETPAY_SITE_ID=...
CINETPAY_SECRET_KEY=...
APP_URL=https://adresse-publique-de-votre-backend
FRONTEND_URL=https://adresse-du-frontend
```

Après modification, exécuter depuis `backend` :

```powershell
php artisan config:clear
php artisan migrate
```

La migration ajoutée autorise `cinetpay` et `especes` comme fournisseurs enregistrés. Elle ne remplace ni ne supprime les paiements existants. La migration n’est pas exécutée automatiquement par ces scripts.

En local, CinetPay doit pouvoir joindre `APP_URL/api/paiements/notification` depuis Internet. `http://localhost:8000` n’est pas accessible depuis les serveurs CinetPay ; utiliser une URL HTTPS de développement publique (tunnel) ou conserver `MOBILE_MONEY_MODE=simulation`.

## 2. Paiement Orange Money / MTN MoMo

1. React demande le numéro dans une fenêtre avant de créer la commande.
2. React crée la commande avec `POST /api/commandes`.
3. React appelle `POST /api/commandes/{commande}/paiement` avec `telephone` et `operateur` (`orange_money` ou `mtn_momo`).
4. Laravel enregistre un paiement `mobile_money` en attente et initialise Checkout v2 auprès de CinetPay.
5. L’API retourne `paiement.url_paiement` ; le navigateur y est redirigé. CinetPay présente les moyens de paiement mobiles activés sur le compte marchand.
6. CinetPay appelle `/api/paiements/notification`. Laravel vérifie le HMAC du header `x-token`, appelle ensuite l’API CinetPay de vérification, puis seulement confirme le paiement et la commande. Un statut `WAITING_FOR_CUSTOMER` garde le paiement en attente ; il n’est pas traité comme un échec. Les points et le parrainage ne sont accordés qu’une fois.

L’opérateur indiqué dans le formulaire est une préférence et une métadonnée. La version Checkout v2 utilisée ici laisse CinetPay afficher les moyens activés sur le compte marchand ; elle ne garantit donc pas que le choix initial force Orange ou MTN. L’utilisateur choisit son moyen sur la page de paiement hébergée.

## 3. Paiement en espèces

1. L’étudiant choisit « Espèces à la remise » dans le panier.
2. `POST /api/commandes/{commande}/paiement-especes` crée un paiement `especes` en attente et confirme la commande afin qu’elle apparaisse dans le service du restaurant.
3. Le paiement n’est pas considéré comme encaissé au moment de la commande ; les points d’achat ne sont pas crédités.
4. Après réception de l’argent, un membre du personnel autorisé appelle `POST /api/staff/commandes/{commande}/encaisser-especes`.
5. Laravel vérifie le restaurant, l’état de commande et le paiement en attente, puis enregistre l’encaissement et crédite les points/parrainage une seule fois.

Une annulation d’un paiement en espèces déjà encaissé marque le paiement comme remboursé dans l’historique ; le remboursement matériel est effectué par le restaurant. Les paiements Mobile Money continuent d’utiliser l’adaptateur de remboursement configuré.

## 4. Essai de l’option espèces avec Postman

Se connecter comme étudiant, copier le jeton Sanctum et l’envoyer dans `Authorization: Bearer <jeton>` :

```http
POST http://localhost:8000/api/commandes
Accept: application/json
Content-Type: application/json
Authorization: Bearer <jeton>
```

Exemple de corps (adapter l’article au menu du jour) :

```json
{
  "lignes": [{"article_id": 1, "quantite": 1}],
  "mode_service": "sur_place",
  "heure_arrivee": "12:00",
  "commentaire": null,
  "utiliser_points": false
}
```

Reprendre `id` renvoyé et envoyer :

```http
POST http://localhost:8000/api/commandes/{id}/paiement-especes
```

La réponse doit indiquer `mode_paiement: "especes"` et `statut_paiement: "en_attente"`. Pour essayer l’encaissement, se connecter avec un compte du personnel associé au restaurant puis envoyer :

```http
POST http://localhost:8000/api/staff/commandes/{id}/encaisser-especes
```

La réponse attendue est `statut: "reussi"`. Une seconde tentative répond `409`, ce qui évite un double encaissement / double crédit de points.

## 5. Documentation API

Les routes sont décrites dans `backend/app/OpenApi/ApiSpecification.php`. Après mise à jour, régénérer Swagger depuis `backend` :

```powershell
php artisan l5-swagger:generate
```

La page habituelle est `http://localhost:8000/api/documentation`.

## Limites actuelles

- Une intégration CinetPay réelle dépend des clés, du site marchand et des méthodes activées dans le compte du propriétaire du projet.
- Le callback doit être accessible publiquement en HTTPS pour les paiements réels.
- Le remboursement CinetPay n’est pas automatisé par l’API Checkout v2 intégrée ici. L’annulation d’une commande déjà payée avec CinetPay ne doit donc pas être présentée comme un remboursement automatique : le remboursement réel doit être fait dans le portail marchand et une évolution dédiée devra synchroniser ce résultat avec la base.
