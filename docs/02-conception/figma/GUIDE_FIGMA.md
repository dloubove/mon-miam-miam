🇫🇷 Français | [🇬🇧 English](GUIDE_FIGMA.en.md)

# Guide Figma — Maquette et prototype de Mon Miam Miam

Ce guide répond à trois questions : de quoi se compose chaque écran, comment les relier dans un prototype, et où trouver des tutoriels et des exemples proches du projet.

**Échéance du Sprint 0** : le prototype Figma doit être terminé le **01/10/2026** (3 jours à partir du 29/09), puis déployé sur Vercel (S0-05).

## 1. Ce que demande le cahier des charges

- Interfaces Figma : **page d'accueil** (menu du jour, promotions, événements) et **page d'authentification** (connexion et inscription).
- **Charte graphique** : police et couleurs (primaire **#cfbd97**, secondaire **#000000**).
- **Maquette interactive** déployée sur **Vercel**, pour présenter les interfaces et les parcours utilisateurs.
- Inspiration proposée dans le cahier : https://dribbble.com/search/restaurant
- Lecture conseillée dans le cahier, sur la différence entre zoning, wireframe, maquette et prototype : https://marcdezordo.me/les-differences-entre-zoning-wireframe-mockup-et-prototype/

Comme le projet compte quatre rôles (étudiant, employé, gérant, administrateur), la maquette doit montrer **les espaces de chaque rôle**, pas seulement l'accueil et la connexion.

> **Important** : aucun modèle existant ne correspond exactement au projet. Il faut combiner un exemple d'application de commande de repas (côté étudiant), un tableau de bord de restaurant (côté employé, gérant, administrateur) et des modèles de fidélité et de parrainage. Ces modèles servent d'**inspiration** : reconstruisez vos écrans avec votre propre charte, sans copier-coller, et vérifiez la licence de chaque fichier.

## 2. Organisation du fichier Figma

**Un seul fichier partagé**, créé dans un espace d'équipe (pas dans les brouillons) pour que Nabil et Nathan travaillent en même temps. Si votre formule gratuite limite le nombre de pages, utilisez des **sections** sur une même page plutôt que plusieurs pages.

| Section | Contenu |
|---|---|
| 00 – Charte et composants | Couleurs, typographie, boutons, champs, cartes, badges, modales |
| 01 – Étudiant (mobile) | Tous les écrans de l'espace étudiant |
| 02 – Employé (bureau) | Écrans de l'espace employé |
| 03 – Gérant (bureau) | Écrans de l'espace gérant |
| 04 – Administrateur (bureau) | Écrans de l'espace administrateur |

**Formats d'écran (frames)** : mobile **390 × 844** pour l'étudiant, bureau **1440 × 900** pour les tableaux de bord du personnel. Réalisez au minimum l'accueil et la connexion en version bureau, puisque l'application est une application web.

**Nommage** : `01-Accueil`, `02-Connexion`, `03-Inscription`… dans l'ordre du parcours. Un bon nommage rend le prototype beaucoup plus facile à relier.

**Répartition conseillée** : Nabil prend l'espace étudiant, Nathan l'espace du personnel (employé, gérant, administrateur). Commencez ensemble par la charte et les composants communs.

## 3. Charte graphique et composants communs

### Charte
- **Couleurs** : primaire #cfbd97, secondaire #000000. Ajoutez des neutres (blanc, gris clair, gris foncé) et des couleurs d'état : succès, avertissement, erreur.
- **Contraste** : le beige #cfbd97 sur fond blanc est peu lisible. Utilisez-le en fond de bouton avec du **texte noir**, et gardez le noir pour le texte courant.
- **Police** : une ou deux familles gratuites (Google Fonts). À noter dans la charte, car elle fait partie des livrables.
- **Styles Figma** : créez des *styles de couleurs* et des *styles de texte* (titre 1, titre 2, corps, légende) et utilisez-les partout. Un changement de charte se répercute alors sur tous les écrans.
- **Grille** : espacements en multiples de 8 px, coins arrondis identiques partout.
- Notez les codes de couleur et la police : l'équipe front-end les réutilisera dans le thème DaisyUI (MM-49).

### Composants à créer une seule fois
Créez-les comme **composants** avec des **variantes** (états) :

| Composant | Variantes |
|---|---|
| Bouton | Principal, secondaire, contour, désactivé |
| Champ de saisie | Normal, actif, erreur, rempli |
| Carte de plat | Normale, épuisée (grisée) |
| Badge de statut | En attente, en préparation, prête ou en livraison, livrée |
| Stepper de quantité | − 1 + |
| Barre de navigation basse (mobile) et menu latéral (bureau) | Élément actif ou non |
| Modale | Confirmation, formulaire, cookies |
| Ligne de tableau | Normale, survolée |
| Message (toast) | Succès, erreur |

Utilisez l'**Auto Layout** (Maj + A) pour que les composants s'adaptent au texte et restent alignés.

## 4. Les écrans et leur composition

Pour chaque écran, pensez aussi aux états : **vide**, **erreur**, **chargement** et **succès**.

### 4.1 Espace étudiant (mobile 390 × 844)

| Écran | Composition | Story |
|---|---|---|
| Accueil | En-tête (logo, bouton Connexion ou avatar), carrousel d'affiches (promotions, événements), section « Menu du jour » (cartes de plats), accès rapides (Menu, Panier, Points), pied de page avec mentions légales, bandeau cookies | MM-05, MM-09, MM-10 |
| Inscription | Nom, email, téléphone, localisation, mot de passe (1 majuscule et 1 chiffre minimum), code de parrainage facultatif, bouton « Créer mon compte », lien vers la connexion, messages d'erreur | MM-01 |
| Connexion | Email, mot de passe (avec icône pour l'afficher), lien « Mot de passe oublié ? », bouton « Se connecter », lien vers l'inscription, message d'erreur | MM-02 |
| Mot de passe oublié | 3 écrans : saisie de l'email, confirmation « lien envoyé », nouveau mot de passe | MM-03 |
| Menu | Onglets de catégories, cartes de plats (photo, nom, prix, étiquette « Épuisé », bouton +), icône de panier avec compteur, barre de navigation basse | MM-06 |
| Panier | Liste des articles (photo, nom, stepper de quantité, suppression), sous-total, bloc « Utiliser mes points », total, bouton « Commander », version « panier vide » | MM-14, MM-23 |
| Validation de commande | Choix « Sur place » (heure d'arrivée) ou « Livraison » (localisation), récapitulatif, paiement en ligne (bonus), bouton « Confirmer », écran de confirmation avec numéro de commande et points gagnés | MM-15, MM-40 |
| Mes commandes | Liste avec badges de statut, détail d'une commande (articles, total, points gagnés), bouton « Laisser un commentaire » (commande livrée), bouton « Faire une réclamation » | MM-16, MM-17 |
| Profil et fidélité | Informations du compte, solde de points bien visible, progression vers la prochaine réduction, historique des points | MM-22, MM-23 |
| Parrainage | Mon code (boutons copier et partager), règles du programme, liste des filleuls avec statut (« récompense attribuée » ou « en attente ») | MM-28 à MM-31 |
| Réclamation | Formulaire (commande concernée, motif, message), écran de suivi avec l'état | MM-32 |
| *Bonus* : Top 10 et mini-jeux | Classement avec filtres jour, semaine, mois ; affiche du jeu avec bouton « Jouer » | MM-38, MM-39 |

### 4.2 Espace employé (bureau 1440 × 900)

| Écran | Composition | Story |
|---|---|---|
| Commandes | Menu latéral (Commandes, Menu, Réclamations, Statistiques), onglets par statut, tableau (n°, client, articles, mode, heure, statut, actions « Préparer » et « Valider »), indicateur d'actualisation automatique, modale de détail | MM-18 |
| Mise à jour du menu | Liste des plats avec interrupteurs « Épuisé » et « Plat du jour » | MM-19 |
| Réclamations | Tableau des réclamations et panneau de réponse | MM-33 |
| Statistiques de la semaine | Cartes de chiffres (ventes, nombre de commandes) et graphique de la semaine | MM-35 |

### 4.3 Espace gérant (bureau)

| Écran | Composition | Story |
|---|---|---|
| Supervision des commandes | Compteurs par statut et tableau des commandes en cours (lecture seule) | MM-20 |
| Comptes employés | Tableau des employés et modale « Ajouter un employé » | MM-08 |
| Réclamations à valider | Liste, réponse proposée par l'employé, boutons « Valider » et « Rejeter » | MM-34 |
| Statistiques générales | Indicateurs et graphiques : ventes, commandes, fidélité, parrainage | MM-36 |

### 4.4 Espace administrateur (bureau)

| Écran | Composition | Story |
|---|---|---|
| Gestion du menu | Tableau des plats et formulaire d'ajout ou de modification (nom, catégorie, prix, photo, disponibilité) | MM-07 |
| Comptes employés | Tableau avec actions modifier et supprimer, confirmation de suppression | MM-08 |
| Promotions et événements | Envoi d'une affiche, dates de début et de fin, aperçu | MM-09 |
| Paramètres | Horaires d'ouverture, taux de conversion des points, durée de validité, points de parrainage | MM-24 |
| Réclamations de points | Ajustement d'un solde avec motif, date et auteur | MM-50 |
| Statistiques | Mêmes indicateurs que le gérant, sur l'ensemble de l'application | MM-36 |

**Écran commun** : la connexion sert à tous les rôles. Dans une maquette, il n'y a pas de logique réelle : prévoyez soit des boutons de démonstration (« Se connecter comme étudiant / employé / gérant / administrateur »), soit un point d'entrée séparé par rôle (voir chapitre 5).

## 5. Relier les écrans : le prototype

### 5.1 Les étapes
1. **Placez les écrans dans l'ordre du parcours**, de gauche à droite, avec des noms clairs.
2. Ouvrez l'onglet **Prototype** (en haut du panneau de droite).
3. Sélectionnez un élément cliquable (un bouton, une carte, une icône), cliquez sur le petit **+** qui apparaît, puis **faites glisser le fil** jusqu'à l'écran de destination. Pour supprimer une connexion, cliquez dessus puis touche Suppr.
4. Dans le panneau de droite, réglez l'interaction : **déclencheur** (On click, On hover…), **action** (Navigate to, Back, Open overlay, Scroll to…), **animation** (Instant, Dissolve, Move in, Push, Smart animate) et sa durée.
5. Définissez le **point de départ du parcours** : sélectionnez l'écran puis cliquez sur « Flow starting point ». Un petit rectangle apparaît en haut à gauche de l'écran. Donnez-lui un nom (par exemple « Parcours commande étudiant »).
6. Testez avec le bouton **Présenter** (▶) en haut à droite. Essayez de suivre chaque parcours comme un vrai utilisateur.

### 5.2 Les techniques utiles
- **Bouton Retour** : action **Back**, pour éviter de tracer un fil vers chaque écran précédent.
- **Modales** (cookies, confirmation de suppression, ajout d'un employé) : action **Open overlay**, avec une action **Close overlay** sur le bouton de fermeture.
- **États des composants** (épuisé, actif, erreur) : créez des **variantes** et reliez-les avec **Smart animate**, par exemple pour un interrupteur ou un stepper de quantité.
- **Barres fixes** (en-tête, barre de navigation basse) : cochez « Fix position when scrolling » pour qu'elles restent visibles pendant le défilement.
- **Survol** : ajoutez un effet « While hovering » sur les boutons et lignes de tableau pour que la maquette paraisse vivante.

### 5.3 Les parcours à prototyper
Un point de départ par parcours, dans cet ordre de priorité :

| Parcours | Enchaînement |
|---|---|
| 1. Inscription | Accueil → Inscription → Accueil connecté |
| 2. Commande étudiant | Connexion → Menu → Panier → Validation → Confirmation → Mes commandes |
| 3. Traitement employé | Connexion → Commandes → Détail → Préparer → Valider |
| 4. Administration du menu | Menu admin → Ajouter un plat → Liste mise à jour |
| 5. Fidélité et parrainage | Profil → Points → Parrainage ; Panier → Utiliser les points |
| 6. Réclamation | Mes commandes → Détail → Réclamation → Suivi ; côté gérant : Valider |
| 7. Promotions et paramètres | Admin → Promotions ; Admin → Paramètres |

## 6. Déployer la maquette sur Vercel

1. Dans Figma, vérifiez que le prototype se lance depuis le bon point de départ, puis utilisez **Partager** pour obtenir un lien de consultation.
2. Figma propose aussi un **code d'intégration** (embed) : à vérifier dans le menu Partager. Collez-le dans une page `index.html` minimale, avec le prototype affiché en plein écran dans une `iframe`.
3. Déposez cette page dans le dépôt GitHub (dossier `docs/` ou un dossier dédié) puis importez le projet sur **Vercel** pour obtenir un lien public.
4. Testez le lien de Vercel dans une fenêtre de navigation privée, pour vérifier que n'importe qui peut ouvrir le prototype.

## 7. Tutoriels et exemples

Les interfaces de Figma évoluent. Dans les vidéos plus anciennes, l'emplacement de certains boutons peut différer, mais les principes du prototypage (Flow starting point, On click, Navigate to, Overlay, Smart animate) restent les mêmes.

### 7.1 Tutoriels YouTube en français
- **Figma pour débutants : les bases** : https://www.youtube.com/watch?v=oBcbcmYfSLk
- **Figma pour débutants : les prototypes** : https://www.youtube.com/watch?v=M0xkv7Sqtc0
- **Le prototype sur Figma : tutoriel avec exemple concret** : https://www.youtube.com/watch?v=tYKkzsAoIG8
- **Prototype, animations et transitions (les bases de Figma, épisode 2)** : https://www.youtube.com/watch?v=Lkn3C4a5NqI
- **Composants interactifs, prototype et variantes** : https://www.youtube.com/watch?v=zhet0av8ITk
- **Formation Figma 2025 : les bases pour bien commencer** : https://www.youtube.com/watch?v=KCoeSoVEMZE
- **TUTO Prototyper 1 (survol, On click, Back, Scroll to)** : https://youtu.be/UzFfrifhmMU

### 7.2 Tutoriels vidéo d'applications de commande de repas (en anglais)
Ils correspondent au côté **étudiant** : menu, panier, commande, paiement, profil.
- **Food Ordering Mobile App Design in Figma** (long tutoriel : wireframes, profil, paiement, prototype) : https://www.youtube.com/watch?v=Yf00MKUfcIY
- **Food Ordering Mobile App Design in Figma (2020)** : https://www.youtube.com/watch?v=O3BmHGNAGhM
- **Food App Design in Figma** : https://www.youtube.com/watch?v=esbdyyEvkxw
- **Design and Prototype a Food Delivery App in Figma** : https://www.youtube.com/watch?v=-zgweOZ6lbI
- **Modern Food Delivery App UI Design in Figma** (de l'accueil jusqu'au paiement) : https://www.youtube.com/watch?v=nh4E48zsfE8
- **Designing a Food Delivery App in Figma** : https://www.youtube.com/watch?v=u06jwiBRWoU

### 7.3 Fichiers Figma Community à consulter ou dupliquer pour s'en inspirer
**Commande de repas (étudiant)**
- Food Delivery App UI Design File (avec tutoriel vidéo) : https://www.figma.com/community/file/1278152924485698833/food-delivery-app-ui-design-file-with-step-by-step-tutorial-youtube
- Food Delivery Website & App Design UI Kit (détail restaurant, commande, panier, paiement, versions mobiles) : https://www.figma.com/community/file/1311333346304045465/food-delivery-website-app-design-ui-kit

**Tableaux de bord du personnel (employé, gérant, administrateur)**
- Davur – Restaurant Admin Dashboard Template (tableau de bord, liste et détail des commandes, clients, analyses, avis) : https://www.figma.com/community/file/1348825795821174985/davur-restaurant-admin-dashboard-template
- Free – Restaurant Management Dashboard : https://www.figma.com/community/file/1377334105126529648/free-restaurant-management-dashboard
- Restaurant Dashboard UI Kit (licence CC BY 4.0) : https://www.figma.com/community/file/1347700614100496995/restaurant-dashboard-ui-kit-editable
- Free Admin Dashboard UI Kit : https://www.figma.com/community/file/1244293267600418871/free-admin-dashboard-ui-kit

**Fidélité et parrainage**
- UI Kit – Loyalty App : https://www.figma.com/community/file/1625344491654069884/ui-kit-loyalty-app
- FindMe – Food App UI Mobile Kit (points échangeables dans des restaurants) : https://www.figma.com/community/file/1343957599586760177/findme-food-app-ui-mobile-kit
- LoyaltyLion – Loyalty Page UI Kit (page de fidélité, versions mobile et bureau) : https://www.figma.com/community/file/1217884680103043804/loyaltylion-loyalty-page-ui-kit
- Free Referral App UI Design (accueil, fenêtre contextuelle, statistiques) : https://www.figma.com/community/file/1088006789763110466/free-referral-app-ui-design
- Referral Program UI + Components : https://www.figma.com/community/file/1281965507164461142/referral-program-ui-components

### 7.4 Articles
- Figma prototyping for beginners (relier les écrans, point de départ) : https://uxplanet.org/figma-prototyping-for-beginners-the-basics-features-and-tips-you-should-know-921ce6c0f846
- Dribbble, recherche « restaurant » (source d'inspiration du cahier des charges) : https://dribbble.com/search/restaurant

## 8. Planning conseillé avant l'échéance du 01/10

| Quand | Nabil (étudiant) | Nathan (personnel) |
|---|---|---|
| **Aujourd'hui, matin** | Wireframes basse fidélité de tous les écrans étudiant | Charte graphique, styles et composants communs |
| **Aujourd'hui, après-midi** | Haute fidélité : accueil, connexion, inscription, menu | Haute fidélité : commandes employé, gestion du menu admin |
| **01/10, matin** | Panier, validation, mes commandes, fidélité | Gérant, paramètres, promotions, statistiques |
| **01/10, après-midi** | Liaison du prototype, tests, déploiement sur Vercel (ensemble) | |

**Priorité si le temps manque** : les écrans **Must** d'abord (accueil, authentification, menu, panier, commande, mes commandes, commandes employé, gestion du menu admin), puis fidélité et parrainage, puis réclamations et statistiques, et en dernier les bonus.

## 9. Liste de contrôle avant la livraison

- [ ] La charte (couleurs #cfbd97 et #000000, police) est documentée dans Figma.
- [ ] Les composants ont leurs variantes et les écrans utilisent les styles partagés.
- [ ] Les écrans de chaque rôle sont présents (étudiant, employé, gérant, administrateur).
- [ ] Les états vide, erreur et succès sont dessinés pour les formulaires principaux.
- [ ] Chaque parcours du chapitre 5.3 a un point de départ nommé et se déroule sans impasse.
- [ ] Le bouton Retour et les modales fonctionnent.
- [ ] Le lien Vercel s'ouvre sans compte, sur ordinateur et sur téléphone.
- [ ] Le lien du prototype est ajouté au dépôt GitHub (README ou dossier `docs/`) et dans Jira (S0-03 et S0-05).
