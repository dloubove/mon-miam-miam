🇫🇷 Français | [🇬🇧 English](README.en.md)

# Maquette Figma sur Vercel

Ce dossier contient une page minimale qui affiche le prototype Figma en plein écran. Vercel la publie à chaque mise à jour de `main`.

## Mise en place (une seule fois)

1. Dans Figma, rendre le prototype consultable par lien, puis récupérer le **code d'intégration** (menu Partager).
2. Coller l'adresse du prototype dans l'attribut `src` de l'`iframe` de `index.html`, par une pull request.
3. Sur https://vercel.com, créer un projet et **importer le dépôt GitHub**.
4. Réglages : **Root Directory** = `prototype-figma`, **Framework Preset** = *Other*, **Production Branch** = `main`.
5. Lancer le déploiement, puis tester l'adresse obtenue dans une fenêtre de navigation privée.
6. Copier l'adresse dans le `README.md` à la racine et dans Jira (S0-05).

## Mises à jour

Chaque fusion dans `main` redéploie la page. Pour changer le prototype Figma sans modifier le lien, il suffit de republier le fichier Figma.
