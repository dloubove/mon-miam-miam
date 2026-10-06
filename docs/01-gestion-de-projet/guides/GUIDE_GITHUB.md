🇫🇷 Français | [🇬🇧 English](GUIDE_GITHUB.en.md)

# Guide GitHub pour l'équipe — Mon Miam Miam

Ce guide explique pas à pas comment travailler à 6 ou 7 sur le même code sans s'écraser mutuellement. Il suffit de le suivre dans l'ordre.

## 1. Comprendre les mots clés

| Mot | Explication simple |
|---|---|
| **Dépôt (repository)** | Le dossier du projet, stocké sur GitHub avec tout son historique. |
| **Clone** | Une copie du dépôt sur votre ordinateur. |
| **Commit** | Une sauvegarde d'un ensemble de modifications, avec un message. |
| **Branche** | Une ligne de travail séparée. On y modifie le code sans toucher aux autres branches. |
| **Push** | Envoyer ses commits de son ordinateur vers GitHub. |
| **Pull** | Récupérer sur son ordinateur ce que les autres ont envoyé sur GitHub. |
| **Pull request (PR)** | Une demande pour fusionner sa branche dans une autre, avec relecture par un collègue. |
| **Merge** | La fusion d'une branche dans une autre. |
| **Conflit** | Deux personnes ont modifié les mêmes lignes : il faut choisir la bonne version. |

## 2. Organisation du dépôt

**Un seul dépôt** avec trois dossiers :

```
mon-miam-miam/
├── backend/    (Laravel)
├── frontend/   (React)
├── docs/       (UML, MCD/MLD, comptes rendus, rapports)
├── .gitignore
└── README.md
```

**Les branches**

| Branche | Rôle | Qui peut y écrire |
|---|---|---|
| `main` | Version stable et livrable | Uniquement par pull request `develop` → `main` en fin de sprint |
| `develop` | Le travail de toute l'équipe, assemblé | Uniquement par pull request depuis une branche `feature/...` |
| `feature/MM-xx-nom` | Une tâche (user story) en cours | Le développeur qui la réalise |

**Nom d'une branche** : `feature/` + la clé Jira + un nom court.
Exemples : `feature/MM-01-inscription`, `feature/MM-15-passer-commande`.

**Message de commit** : la clé Jira, deux points, puis ce qui a été fait, au présent.
Exemples : `MM-01: ajoute le formulaire d'inscription`, `MM-15: calcule le total du panier`.

## 3. Installation initiale (chaque membre, une seule fois)

1. **Créer un compte GitHub** et communiquer son nom d'utilisateur au chef de projet.
2. **Installer Git** : https://git-scm.com/downloads
3. **Facultatif mais conseillé pour débuter** : installer **GitHub Desktop** ou utiliser l'onglet Git de **VS Code**. Ils font les mêmes actions avec des boutons. Les commandes de ce guide restent valables.
4. **Configurer son identité** dans un terminal :
   ```
   git config --global user.name "Prénom Nom"
   git config --global user.email "votre@email.com"
   ```
   Utiliser la même adresse email que celle du compte GitHub.
5. **Accepter l'invitation** au dépôt reçue par email (ou sur GitHub, dans les notifications).
6. **Cloner le dépôt** :
   ```
   git clone <url-du-depot>
   cd mon-miam-miam
   git checkout develop
   ```

## 4. Mise en place du dépôt (chef de projet, une seule fois)

1. Sur GitHub : **New repository**, nom `mon-miam-miam`, visibilité privée, cocher « Add a README ».
2. **Cloner** le dépôt, puis créer les dossiers `backend/`, `frontend/` et `docs/` (Git ne garde pas les dossiers vides : ajouter un fichier `.gitkeep` dans chacun).
3. **Créer le `.gitignore`** avec au minimum :
   ```
   .env
   vendor/
   node_modules/
   .idea/
   .vscode/
   storage/*.key
   ```
4. **Créer la branche `develop`** et l'envoyer :
   ```
   git add .
   git commit -m "Initialise la structure du projet"
   git push origin main
   git checkout -b develop
   git push -u origin develop
   ```
5. **Inviter l'équipe** : *Settings → Collaborators → Add people*, avec les droits d'écriture.
6. **Mettre `develop` comme branche par défaut** : *Settings → Branches → Default branch*. Les nouvelles pull requests viseront ainsi `develop` automatiquement.
7. **Protéger `main` et `develop`** : *Settings → Branches → Add branch protection rule*. Cocher « Require a pull request before merging » et « Require approvals » (1 approbation).

> **Attention** : sur un dépôt **privé** avec un compte GitHub gratuit, la protection de branches n'est pas disponible. Solutions : demander le **GitHub Student Developer Pack** (comptes Pro gratuits pour étudiants), ou rendre le dépôt public, ou s'imposer la règle sans blocage technique. Dans tous les cas, les règles du chapitre 9 s'appliquent.

8. **Connecter GitHub à Jira** (facultatif) : les branches et pull requests contenant la clé `MM-xx` s'affichent alors sur la carte Jira.

## 5. Le cycle de travail pour chaque tâche

Suivre ces 8 étapes **pour chaque user story**.

### Étape 1 — Se mettre à jour
```
git checkout develop
git pull origin develop
```
*Pourquoi ?* Pour partir du travail le plus récent de l'équipe.

### Étape 2 — Créer sa branche
```
git checkout -b feature/MM-15-passer-commande
```
*Pourquoi ?* Pour travailler sans gêner les autres. Vérifier avec `git branch` que la branche active (marquée d'une étoile) est la bonne.

### Étape 3 — Travailler et faire des commits
Après chaque petit morceau de travail terminé :
```
git status
git add .
git commit -m "MM-15: ajoute le panier"
```
- `git status` montre les fichiers modifiés.
- `git add .` prépare tous les fichiers modifiés. Vérifier avant qu'aucun fichier secret (`.env`) n'y figure.
- Faire des commits **fréquents et petits**, plutôt qu'un gros commit en fin de journée.

### Étape 4 — Envoyer sa branche sur GitHub
La première fois :
```
git push -u origin feature/MM-15-passer-commande
```
Ensuite, un simple `git push` suffit.

### Étape 5 — Se resynchroniser avant de proposer son travail
Pendant que vous travailliez, d'autres ont peut-être fusionné du code dans `develop`. Le récupérer :
```
git pull origin develop
```
- S'il y a un **conflit**, voir le chapitre 7.
- Si un éditeur de texte s'ouvre pour un message de fusion, enregistrer et fermer (dans Vim : touche `Échap`, puis taper `:wq` et `Entrée`).
- **Tester** que l'application fonctionne toujours, puis `git push`.

### Étape 6 — Ouvrir une pull request
1. Sur GitHub, cliquer sur **Compare & pull request** (ou *Pull requests → New pull request*).
2. Vérifier : **base : `develop`** et **compare : votre branche**.
3. **Titre** : `MM-15 Passer commande`.
4. **Description** : ce qui a été fait, comment le tester, les critères d'acceptation couverts.
5. Assigner **au moins un relecteur** (*Reviewers*).
6. Prévenir le relecteur dans le groupe de discussion.

### Étape 7 — La relecture
Voir le chapitre 6.

### Étape 8 — Fusionner puis nettoyer
Quand la pull request est approuvée :
1. Cliquer sur **Squash and merge** (réunit tous les commits en un seul, ce qui garde l'historique lisible), puis **Confirm**.
2. Cliquer sur **Delete branch**.
3. En local :
   ```
   git checkout develop
   git pull origin develop
   git branch -d feature/MM-15-passer-commande
   ```
4. Passer la carte Jira en **Done** (après validation du PO à la Sprint Review).

## 6. Faire et recevoir une relecture

**Le relecteur** (moins d'une journée, pour ne pas bloquer les autres) :
1. Ouvrir l'onglet **Files changed** de la pull request.
2. Lire le code. Poser des commentaires ligne par ligne au besoin.
3. Vérifier la checklist ci-dessous.
4. Cliquer sur **Review changes** : **Approve** si tout est bon, **Request changes** sinon.

**Checklist de relecture**
- Le code répond aux critères d'acceptation de la user story.
- Aucun secret, aucun `.env`, aucun mot de passe dans le code.
- Les noms sont clairs, pas de code mort ni de `console.log` oubliés.
- Des tests existent pour la logique importante.
- Le rendu correspond à la maquette Figma et à la charte (#cfbd97 / #000000).

**L'auteur** : corriger, refaire `git add`, `git commit` et `git push` sur **la même branche**. La pull request se met à jour toute seule.

## 7. Résoudre un conflit

Un conflit apparaît lors d'un `git pull` ou d'une fusion quand deux personnes ont modifié les mêmes lignes. Git le signale :

```
Auto-merging routes/web.php
CONFLICT (content): Merge conflict in routes/web.php
```

**Que faire ?**
1. Ouvrir le fichier concerné. Git y a ajouté des marqueurs :
   ```
   <<<<<<< HEAD
   votre version
   =======
   la version de l'autre
   >>>>>>> origin/develop
   ```
2. Décider : garder l'une, l'autre, ou combiner les deux.
3. **Supprimer les trois marqueurs** (`<<<<<<<`, `=======`, `>>>>>>>`).
4. Enregistrer, puis :
   ```
   git add routes/web.php
   git commit
   ```
5. Tester l'application, puis `git push`.

VS Code propose des boutons « Accept Current / Incoming / Both » qui facilitent cette étape. En cas de doute, appeler l'autre personne concernée plutôt que de deviner.

**Comment éviter les conflits**
- Branches courtes : 1 à 2 jours maximum.
- `git pull origin develop` chaque matin et avant chaque pull request.
- Prévenir l'équipe avant de modifier un fichier partagé (`routes/web.php`, `App.jsx`, fichiers de configuration).
- Ne jamais modifier une migration Laravel déjà fusionnée : en créer une nouvelle.
- Ne pas reformater des fichiers entiers sans nécessité (cela crée des conflits inutiles).

## 8. Fin de sprint

Après la Sprint Review et la validation du Product Owner :
1. Ouvrir une pull request **`develop` → `main`**, avec pour titre « Sprint X ».
2. La faire relire par le Scrum Master ou le PO, puis fusionner avec **Create a merge commit** (et non Squash, pour conserver l'historique des tâches).
3. Poser une étiquette (tag) sur la version livrée :
   ```
   git checkout main
   git pull origin main
   git tag sprint-1
   git push origin sprint-1
   ```
4. Revenir sur `develop` pour continuer : `git checkout develop`.

## 9. Règles d'or

1. **Jamais de push direct** sur `main` ni sur `develop`.
2. **Jamais de `git push --force`** sur une branche partagée.
3. **Une branche par user story**, avec la clé Jira dans le nom.
4. **Aucun secret dans le dépôt** : `.env`, clés d'API, mots de passe. Fournir un fichier `.env.example` avec des valeurs factices.
5. **Un commit = un changement clair**, avec un message qui commence par la clé Jira.
6. **Se mettre à jour avant de commencer** : `git pull origin develop`.
7. **Relire dans la journée** : une pull request en attente bloque quelqu'un.
8. **Ne pas s'approprier la branche d'un autre** sans le prévenir.

## 10. Problèmes fréquents

| Problème | Solution |
|---|---|
| J'ai codé sans créer de branche (sur `develop`), rien n'est encore commité | `git checkout -b feature/MM-xx-nom` : les modifications suivent sur la nouvelle branche. |
| J'ai déjà commité sur `develop` par erreur (sans push) | Demander de l'aide au chef de projet avant de tenter une manipulation. |
| `git push` est refusé (« rejected », « non-fast-forward ») | Quelqu'un a poussé avant vous : `git pull origin <votre-branche>`, corriger les conflits, puis `git push`. |
| Je dois changer de branche mais j'ai du travail non terminé | `git stash` (met le travail de côté), changer de branche, puis `git stash pop` pour le récupérer. |
| Je veux annuler mes modifications non commitées sur un fichier | `git restore fichier`. **Attention : c'est irréversible.** |
| J'ai commité le fichier `.env` | Prévenir immédiatement le chef de projet, retirer le fichier (`git rm --cached .env`), **changer tous les secrets** qu'il contenait (ils restent visibles dans l'historique). |
| Je ne sais plus où j'en suis | `git status` (état actuel) et `git log --oneline` (derniers commits). |
| Je ne vois pas la branche d'un collègue | `git fetch`, puis `git checkout nom-de-la-branche`. |

## 11. Aide-mémoire

```
git status                          # voir l'état
git branch                          # voir sa branche active
git checkout develop                # aller sur develop
git pull origin develop             # récupérer le travail des autres
git checkout -b feature/MM-xx-nom   # créer sa branche
git add .                           # préparer les modifications
git commit -m "MM-xx: message"      # sauvegarder
git push -u origin feature/MM-xx-nom  # envoyer (1re fois)
git push                            # envoyer (ensuite)
git stash / git stash pop           # mettre de côté / récupérer
git log --oneline                   # historique court
```

**Ordre de travail : `pull` → branche → `commit` → `pull develop` → `push` → pull request → relecture → fusion.**
