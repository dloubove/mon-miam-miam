🇫🇷 Français | [🇬🇧 English](CONTRIBUTING.en.md)

# Contribuer à Mon Miam Miam

Guide détaillé pas à pas : `docs/01-gestion-de-projet/guides/GUIDE_GITHUB.md`.

## Branches

| Branche | Rôle |
|---|---|
| `main` | Version stable et livrable, mise à jour uniquement par pull request `develop` → `main` en fin de sprint |
| `develop` | Intégration du travail de l'équipe, mise à jour uniquement par pull request |
| `feature/MM-xx-nom-court` | Une tâche (user story), créée depuis `develop` |

Exemples : `feature/MM-01-inscription`, `feature/MM-15-passer-commande`.

## Cycle de travail

1. `git checkout develop` puis `git pull origin develop`
2. `git checkout -b feature/MM-xx-nom-court`
3. Travailler, puis `git add .` et `git commit -m "MM-xx: ce qui a été fait"` (petits commits fréquents)
4. `git push -u origin feature/MM-xx-nom-court`
5. Avant de proposer le travail : `git pull origin develop`, corriger les conflits, tester
6. Ouvrir une pull request vers `develop` (le modèle de PR se remplit tout seul)
7. Un autre membre relit dans la journée, puis la PR est fusionnée avec **Squash and merge**
8. Supprimer la branche, puis `git checkout develop` et `git pull origin develop`

## Règles

1. Jamais de push direct sur `main` ni `develop`.
2. Jamais de `git push --force` sur une branche partagée.
3. Une branche par user story, avec la clé Jira dans le nom.
4. Aucun secret dans le dépôt : pas de `.env`, de clé d'API ni de mot de passe. Chaque dossier fournit un `.env.example` avec des valeurs factices.
5. Les messages de commit commencent par la clé Jira.
6. Une pull request en attente bloque quelqu'un : relire dans la journée.
7. Ne pas modifier une migration Laravel déjà fusionnée : en créer une nouvelle.

## Definition of Done

- Code relu par au moins un pair via pull request
- Tests unitaires écrits et au vert
- Conforme à la maquette Figma et à la charte (#cfbd97 et #000000)
- Fonctionnel sur Chrome, Firefox et Safari
- Critères d'acceptation validés par le Product Owner
