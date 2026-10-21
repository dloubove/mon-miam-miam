# Frontend — Mon Miam Miam (ZeDuc@Space)

React 19 + Vite + Tailwind 4 + DaisyUI 5 + React Router. Thème sombre fidèle à la maquette « Site ZeDuc@Space ».

## Lancer
```bash
npm install
npm run dev      # http://localhost:5173
npm run build
npm run lint
```

## Comptes de démonstration (mot de passe libre)
| Rôle | Email | Espace |
|---|---|---|
| Étudiant | etudiant@ucac-icam.com | /mon-espace |
| Employé | paul@zeduc.com | /staff |
| Gérant | marc@zeduc.com | /direction |
| Administrateur | admin@zeduc.com | /direction (+ équipe, règles) |

En mode dev, des boutons « Accès démo » sont aussi présents sous le formulaire de connexion.

## Pages
`/` accueil · `/menu/plats` et `/menu/boissons` (catalogue filtrable) · `/connexion` · `/inscription` · `/panier` · `/mon-espace` · `/mon-espace/profil` · `/jeux` · `/jeux/roue` · `/jeux/devine-le-plat` ·
`/staff` · `/staff/menu` · `/staff/reclamations` · `/staff/profil` ·
`/direction` · `/direction/catalogue` · `/direction/equipe` (admin) · `/direction/marketing` · `/direction/profil`

Toutes les modales de la maquette sont implémentées (ajout panier, validation commande, suivi livreur, signalement, suppression de compte,
composer le menu, traiter une réclamation, nouvel événement, nouveau membre, nouveau plat).

## Architecture
- `src/context/AppContext.jsx` : état global **local** (auth, panier, commandes, réclamations, menu, équipe, événements, règles).
  Chaque bloc est à remplacer par des appels à l'API Laravel (`VITE_API_URL`).
- `src/data/mock.js` : données de démonstration.
- `src/layouts/` : navbar étudiant, staff, sidebar direction, écran d'authentification.
- `src/pages/` : une page par écran de la maquette.

## Points à brancher sur le backend
Authentification réelle (tokens), paiement Mobile Money/carte, persistance des commandes/points, envoi d'email (mot de passe oublié),
upload des images (aujourd'hui en base64 local), remplacement des images de `public/images` par les photos d'origine.

## Déconnexion rapide
Clic sur l'avatar (étudiant, staff, panier) → menu avec « Se déconnecter » ; dans l'espace Direction, bouton rouge permanent dans la sidebar (icône dans la barre mobile).
