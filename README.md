🇫🇷 Français | [🇬🇧 English](README.en.md)

# Mon Miam Miam

Plateforme web de commande de repas pour le restaurant **ZeDuc@Space** : commande sur place ou en livraison, programme de fidélité, parrainage, réclamations, et espaces dédiés (étudiant, employé, gérant, administrateur).

Projet du **groupe 9**, réalisé du 30/09/2026 au 21/10/2026 avec la méthode Scrum.

## Structure du dépôt

```
mon-miam-miam/
├── backend/            API REST Laravel (à initialiser, voir ci-dessous)
├── frontend/           Application React (à initialiser)
├── prototype-figma/    Maquette Figma déployée sur Vercel
├── docs/               Tous les livrables non techniques et la conception
├── .github/            Modèles de pull request, tickets et intégration continue
├── .vscode/            Réglages et extensions recommandées pour VS Code
├── CONTRIBUTING.md     Comment contribuer (branches, commits, pull requests)
└── README.md
```

Le dossier `docs/` est organisé par livrable : voir [docs/README.md](docs/README.md).

## Technologies

- **Backend** : Laravel (PHP), API REST
- **Frontend** : React, TailwindCSS, DaisyUI
- **Base de données** : PostgreSQL
- **Maquette** : Figma, déployée sur Vercel
- **Outils** : GitHub, Jira, VS Code

## Liens du projet

- Maquette Figma : _(à compléter)_
- Maquette en ligne (Vercel) : _(à compléter)_
- Jira : _(à compléter)_

## Démarrer

```
git clone <url-du-depot>
cd mon-miam-miam
git checkout develop
code .
```

VS Code propose d'installer les extensions recommandées : accepter.
Les consignes d'installation du backend et du frontend seront ajoutées dans `docs/03-technique/installation` dès leur initialisation.

## Contribuer

Personne ne pousse directement sur `main` ni `develop`. Tout passe par une pull request relue par un autre membre : voir [CONTRIBUTING.md](CONTRIBUTING.md).

## Équipe — Groupe 9

Dan (Product Owner), Julio, Nabil, Nathan, Siméon, Kadir, Loïs.
