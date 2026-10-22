# Mon Miam Miam — corrections apportées

## Corrections incluses

- Une seule configuration Vite de référence : `frontend/vite.config.js` produit le manifeste attendu par Laravel dans `backend/public/build/manifest.json`.
- `backend/package.json` délègue `build` et `dev` au frontend ; l'ancienne configuration Vite concurrente du backend a été supprimée pour éviter qu'elle écrase le manifeste React/Inertia.
- Le build synchronise les images statiques de `frontend/public/images` vers `backend/public/images`, où Laravel les sert réellement.
- Le logo officiel fourni a été ajouté sous `frontend/public/images/logo-zeduc-space.png` et `backend/public/images/logo-zeduc-space.png`, puis intégré aux en-têtes, au footer et au favicon.
- Les photos des plats et l'affiche de la hero section utilisent `object-cover` avec largeur et hauteur complètes.
- Les espaces gérant/administrateur disposent d'accès aux pages de supervision des commandes, statistiques, réclamations et gestion de l'équipe.
- Ajout d'une page de statistiques hebdomadaires réutilisant l'API `GET /api/staff/statistiques`.
- Ajout d'une page de suivi des filleuls réutilisant l'API existante `GET /api/parrainages`.
- CI mise à jour pour utiliser Node.js 22 et vérifier le manifeste et les images après compilation.

## Installation locale (Windows / PowerShell)

Depuis la racine du dépôt :

```powershell
cd frontend
npm install
npm run build
cd ../backend
copy .env.example .env # seulement si .env n'existe pas déjà
php artisan key:generate # seulement si APP_KEY est vide
php artisan storage:link
php artisan serve
```

Configure la base PostgreSQL dans `backend/.env` et exécute les migrations/seeders selon le README backend. Ne remplace pas ton `.env` existant par `.env.example`.

Pour le développement avec rechargement à chaud, depuis `backend/` après installation des dépendances :

```powershell
composer run dev
```

Ou lance séparément `php artisan serve` depuis `backend/` et `npm run dev` depuis `frontend/`.

## Limite fonctionnelle à connaître

Le backend actuel gère automatiquement la récompense du parrain après la première commande payée du filleul. Le cahier des charges ne définit pas de statut d'approbation manuelle du filleul ; la page affiche donc les filleuls, l'état de la première commande et l'état de la récompense, sans inventer un bouton d'acceptation qui n'aurait pas d'endpoint serveur.

## Validation

Les fichiers de configuration JSON et le script de copie des images ont été vérifiés. La compilation Vite complète n'a pas pu être exécutée dans l'environnement de préparation, car l'installation des dépendances npm n'a pas abouti dans le temps disponible. Exécute `npm install` puis `npm run build` localement et consulte toute erreur éventuelle avant le déploiement.
