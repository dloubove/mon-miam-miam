# Guide détaillé des domaines et de la logique PostgreSQL

## But du guide

Ce guide complète la documentation d’installation. Il explique pourquoi le schéma utilise des domaines PostgreSQL, quelles règles sont protégées par les contraintes, et comment les vues, requêtes et déclencheurs fonctionnent. Les valeurs de conversion des points proviennent du cahier des charges : 1 point gagné par tranche de 1 000 FCFA dépensés et 1 000 FCFA de remise pour 15 points utilisés.

## 1. Pourquoi utiliser des domaines

Un domaine PostgreSQL est un type construit à partir d’un type de base et éventuellement d’une règle `CHECK`. Il permet de nommer une règle métier et de la réutiliser dans plusieurs colonnes. Par exemple, `montant_fcfa` signifie qu’un montant est un nombre entier positif ou nul, tandis que `statut_commande` n’accepte que les états prévus par l’application.

Les domaines rendent le schéma plus lisible et évitent de recopier des contraintes identiques. Ils centralisent aussi les modifications : une évolution apportée à un domaine peut être appliquée à toutes les colonnes qui l’utilisent. Ils n’imposent toutefois pas à eux seuls qu’une valeur soit renseignée : le `NOT NULL` reste déclaré sur chaque colonne obligatoire.

### Domaines déclarés dans `MMM_cre.sql`

| Domaine | Type de base et règle | Pourquoi cette règle |
|---|---|---|
| `id_entite` | `integer`, strictement supérieur à 0 | Donne un type commun aux identifiants. Les identifiants sont générés par identité sur les tables principales. |
| `nom_long` | `varchar(150)`, texte non vide après `trim` | Écarte les noms et titres vides pour les utilisateurs, articles, rôles, événements, etc. |
| `email` | `varchar(150)`, motif sommaire contenant `@` et un point | Vérification légère côté base ; le contrôle réel et la vérification d’unicité restent nécessaires dans l’application et via `UNIQUE`. |
| `telephone` | `varchar(20)`, chiffres et caractères téléphoniques usuels | Autorise les préfixes internationaux et les séparateurs sans stocker un numéro comme quantité. |
| `montant_fcfa` | `numeric(10,0)`, montant supérieur ou égal à 0 | Le FCFA est stocké sans décimales dans le modèle. |
| `quantite` | `integer`, strictement supérieur à 0 | Une ligne de commande doit contenir au moins un article. |
| `points` | `integer` | Le signe est vérifié selon le contexte : gain positif, utilisation/expiration négative, solde étudiant non négatif. |
| `date_op` | `date`, date au moins égale au 1er janvier 2020 | Écarte des dates métier manifestement invalides dans ce projet. |
| `horodatage` | `timestamp`, au moins égal au 1er janvier 2020 | Réserve le type aux horodatages métiers non antérieurs à la période du projet. |
| `statut_compte` | texte fermé : `actif`, `inactif` | Empêche les statuts incohérents des comptes. |
| `libelle_role` | `employe`, `gerant`, `administrateur` | Ferme la liste des rôles connus dans cette version. |
| `mode_service` | `sur_place`, `livraison` | Assure une valeur normalisée pour le choix du service. |
| `statut_commande` | panier, attente, préparation, prête, servie, livrée, annulée | Empêche la création de statuts écrits différemment. |
| `mode_paiement` | carte, mobile money, points, espèces | Normalise les moyens de paiement. L’utilisation de points comme remise est aussi enregistrée dans le grand livre. |
| `statut_paiement` | attente, réussi, échoué, remboursé | Distingue une tentative d’un règlement confirmé. |
| `type_mouvement` | gains, utilisation, expiration, ajustement | Catégorise les écritures du grand livre de fidélité. |
| `statut_reclamation` | ouverte, répondue, validée, rejetée | Normalise le cycle de traitement d’une réclamation. |
| `decision` | validée ou rejetée | Restreint la décision de gestion à ses deux issues. |
| `type_evenement` | événement ou mini-jeu | Valeurs conservées du dictionnaire fourni. Les jeux ont maintenant leurs propres tables. |
| `statut_mini_jeu` | actif ou inactif | Interdit les autres états pour un jeu. |

Les domaines de codes fermés conviennent à un vocabulaire stable et petit. Si l’administrateur doit créer de nouveaux statuts ou rôles sans déploiement SQL, il faudra remplacer le domaine par une table de référence ou revoir le modèle.

## 2. Contraintes des tables

Les contraintes déclaratives sont évaluées par PostgreSQL quel que soit le client utilisé.

- **Clés primaires (`PRIMARY KEY`)** : identifient chaque ligne et empêchent les doublons d’identifiant.
- **Clés étrangères (`FOREIGN KEY`)** : interdisent les références à des parents absents. La plupart des relations utilisent `ON DELETE RESTRICT` pour préserver commandes et historiques. COMPOSER et CONTENIR utilisent la suppression en cascade avec leur parent d’association.
- **Unicité (`UNIQUE`)** : email, matricule, code de parrainage, nom de mini-jeu, et clés restaurant/date ou restaurant/paramètre. Les clés composées des tables d’association interdisent les doublons.
- **`CHECK`** : bornes des prix, quantités, soldes, gains, dates de validité, remise maximale et cohérence du mode de service.
- **`NOT NULL`** : utilisé séparément du domaine pour les champs obligatoires. Les champs optionnels restent nullables, par exemple adresse de livraison d’une commande sur place ou données de réponse d’une réclamation ouverte.
- **Index partiels** : un seul mouvement `gain_commande` et un seul mouvement `utilisation` par commande. Ils rendent les traitements rejouables sans créditer ou débiter deux fois.
- **Solde contrôlé** : le champ `solde_points` ne se modifie pas directement ; toute variation doit provenir d’une écriture du grand livre. Un solde de départ différent de zéro doit être initialisé par un mouvement d’ajustement.

Pour une réclamation décidée, la réponse et sa date doivent exister, ainsi que la date de décision, qui ne peut précéder la réponse. Une commande doit avoir une heure d’arrivée si elle est sur place ou une adresse si elle est livrée. Les prix et quantités sont strictement positifs.

## 3. Vues et requêtes

Les vues sont des requêtes nommées, calculées à la lecture. Elles ne dupliquent pas les données.

### `v_solde_points`

Compare `ETUDIANT.solde_points`, conservé pour lire rapidement le solde, à la somme signée de `MOUVEMENT_POINTS.nombre_points`. Le grand livre est la source de vérité. En fonctionnement normal, les deux chiffres doivent être égaux.

```sql
SELECT * FROM mmm.v_solde_points WHERE solde_memoire <> solde_calcule;
```

Cette requête ne doit renvoyer aucune ligne après le chargement du schéma et des données.

### `v_ventes_journalieres`

Regroupe par restaurant et jour le nombre de commandes ainsi que le montant des paiements dont le statut est `reussi`. Pour une période, l’application peut filtrer les colonnes `jour` et `id_restaurant`.

```sql
SELECT jour, commandes, total_encaisse_fcfa
FROM mmm.v_ventes_journalieres
WHERE id_restaurant = 1 AND jour >= current_date - 7
ORDER BY jour;
```

### `v_top_etudiants`

Expose le solde et le nombre de commandes terminées par étudiant. Pour afficher les dix premiers :

```sql
SELECT id_utilisateur, nom, solde_points, commandes_terminees
FROM mmm.v_top_etudiants
ORDER BY commandes_terminees DESC, id_utilisateur
LIMIT 10;
```

La vue compte `servie` et `livree`. Des filtres jour/semaine/mois sont à appliquer sur la date des commandes dans une requête dédiée, car cette vue donne le total historique.

Exemple réutilisable pour une période choisie (remplacer `:debut` et `:fin` par les bornes voulues) :

```sql
SELECT e.id_utilisateur, u.nom, count(*) AS commandes_terminees
FROM mmm.etudiant e
JOIN mmm.utilisateur u USING (id_utilisateur)
JOIN mmm.commande c ON c.id_etudiant=e.id_utilisateur
WHERE c.statut_commande IN ('servie','livree')
  AND c.date_commande >= :debut AND c.date_commande < :fin
GROUP BY e.id_utilisateur,u.nom
ORDER BY commandes_terminees DESC,e.id_utilisateur
LIMIT 10;
```

Pour le jour courant, utiliser `current_date` comme début et `current_date + 1` comme fin. Pour une semaine ou un mois, l’application calcule les bornes correspondantes puis réutilise la même requête.

### `v_classement_mini_jeux`

Agrège les parties par jeu et étudiant : nombre de parties, meilleur score et total des points gagnés.

### `referrals`

Présente la relation parrain/filleul stockée dans `ETUDIANT`, avec le code et l’état de récompense. C’est une vue de compatibilité avec le nom `referrals` cité dans le cahier des charges ; elle n’ajoute pas une table redondante.

## 4. Déclencheurs et fonctions

`MMM_inv.sql` est chargé après la création des tables. Les fonctions PL/pgSQL y sont appelées automatiquement par les déclencheurs.

| Fonction / déclencheur | Moment | Effet métier |
|---|---|---|
| `verifier_sous_type_utilisateur` / `ct_utilisateur_sous_type` | Fin de transaction après création/modification d’un compte | Vérifie que le compte a exactement un profil ETUDIANT ou PERSONNEL. Le compte et son profil doivent être créés dans la même transaction. |
| `exclusivite_sous_types` | Avant insertion/modification du profil étudiant ou personnel | Empêche d’avoir les deux sous-types pour le même compte. |
| `verifier_commande_non_vide` | Fin de transaction après écriture commande/lignes | Toute commande dont le statut n’est plus `panier` doit avoir au moins une ligne. Le report permet d’insérer les lignes après la commande dans la même transaction. |
| `figer_prix_article` | Avant insertion/modification de CONTENIR | Copie le prix courant de l’article dans `prix_applique`, afin de garder le prix historique. |
| `controler_points_commande` | Avant confirmation du panier | Vérifie le seuil et le solde disponible, applique une promotion active si fournie, calcule la remise points et recalcule le montant net à payer depuis les lignes. Une commande doit d’abord être créée comme panier. |
| `debiter_points_commande` | Après le passage du panier à un autre statut | Ajoute un mouvement négatif d’utilisation avec l’identifiant de commande. Les points ne peuvent être modifiés après confirmation. |
| `appliquer_mouvement_points` | Après insertion d’un mouvement | Met à jour le solde étudiant et refuse un solde négatif. Tout crédit, débit, expiration ou ajustement passe donc par le grand livre. |
| `proteger_solde_etudiant` | Avant insertion/modification du solde | Refuse l’initialisation ou la modification directe d’un solde hors déclencheur du grand livre. |
| `mouvement_immuable` | Avant modification/suppression d’un mouvement | Protège l’audit : une correction se fait avec un nouveau mouvement d’ajustement. |
| `crediter_commande_payee` | Après insertion ou passage d’un paiement à réussi | Attend que la somme des paiements réussis couvre `montant_total`, puis crédite une seule fois les points calculés sur le montant net. |
| `recompenser_parrainage` | Après un paiement réussi | Au règlement complet, attribue une seule fois au parrain le nombre `points_parrainage` configuré sur le profil du filleul. |
| `controler_jeton` | Avant insertion/modification d’un jeton | Refuse la consommation d’un jeton expiré et interdit de réactiver un jeton consommé. |
| `valider_partie_mini_jeu` | Avant insertion/modification d’une partie | Refuse un jeu inactif et un gain supérieur au plafond. |
| `crediter_partie_mini_jeu` | Après insertion d’une partie | Crée un mouvement positif avec date d’expiration. |
| `interdire_modification_partie` | Avant modification/suppression de partie | Préserve l’historique des scores et des gains. |
| `expirer_points_etudiant` | Appel explicite ou dans le contrôle d’une dépense | Expire les lots arrivés à échéance dans l’ordre FIFO, sans supprimer les mouvements d’origine. |
| `traiter_expiration_points` | Appel de l’ordonnanceur | Parcourt les étudiants et applique l’expiration à chacun. |

### Cycle d’une commande avec points

1. Insérer COMMANDE au statut `panier`.
2. Insérer une ou plusieurs lignes CONTENIR. Le déclencheur lit les prix courants des articles.
3. Mettre `points_utilises` si l’étudiant souhaite une remise.
4. Mettre le statut à `en_attente`. Dans la même transaction, PostgreSQL recalcule le total depuis les lignes, valide le solde et le seuil, consomme les points par mouvement et vérifie que la commande n’est pas vide.
5. Ajouter les paiements. Plusieurs tentatives ou règlements partiels sont possibles. Un gain de fidélité ne part que lorsque les paiements réussis couvrent la totalité du montant dû.
6. Le passage à `reussi` crée le gain de commande une seule fois. Le gain est une tranche complète de 1 000 FCFA payés par défaut.

Exemple de calcul sur une commande de 2 500 FCFA :

| Opération | Calcul | Résultat |
|---|---:|---:|
| Utilisation | 15 points | 1 000 FCFA de remise |
| Montant net | 2 500 - 1 000 | 1 500 FCFA à payer |
| Gain à règlement complet | partie entière de 1 500 / 1 000 | 1 point |

Les opérations de confirmation, mouvements et changement d’état doivent rester dans la même transaction de base afin que tout soit annulé ensemble si une règle échoue.

## 5. Paramètres de fidélité et expiration

`MMM_ref.sql` ajoute, par restaurant :

- `points_par_1000_fcfa = 1` : nombre de points attribués par tranche de 1 000 FCFA payés ;
- `points_par_tranche_reduction = 15` : nombre de points correspondant à une tranche de remise ;
- `fcfa_pour_15_points = 1000` : réduction en FCFA pour chaque tranche de 15 points utilisée ;
- `seuil_min_points_utilisation = 15` : minimum et pas d’utilisation ;
- `duree_validite_points_mois = 12` : durée indicative de validité.

Les taux du cahier sont des exemples de départ et peuvent être modifiés. Le nombre utilisé doit être au moins égal au seuil et être un multiple de `points_par_tranche_reduction`. Le contrôle de dépense appelle l’expiration pour cet étudiant avant de consulter son solde.

Pour traiter l’expiration automatiquement, l’environnement doit planifier quotidiennement :

```sql
SELECT mmm.traiter_expiration_points();
```

La base ne peut pas démarrer seule une tâche périodique sans ordonnanceur (par exemple un planificateur côté application ou une extension PostgreSQL configurée). L’expiration est conservée comme débit au lieu d’effacer l’historique.

## 6. Sécurité et limites restantes

- Les fonctions métier supposent que l’application ouvre une transaction autour de la confirmation de commande et qu’elle ne met pas à jour directement les soldes.
- La génération des codes de parrainage, le hachage des mots de passe, l’envoi des jetons, la vérification d’identité et les appels d’agrégateur de paiement sont des responsabilités de l’application.
- Les insertions de `MOUVEMENT_POINTS` doivent être réservées à des rôles PostgreSQL contrôlés en production. L’utilisateur de l’application ne devrait pas pouvoir écrire des mouvements arbitraires de type `ajustement`.
- Le modèle actuel ne fournit pas de procédure de remboursement qui annule les gains déjà consommés après le remboursement d’un paiement. Le flux de remboursement doit être spécifié avant l’activation de remboursements après attribution de points.
- Les gains de mini-jeux utilisent la durée de validité du premier restaurant, car MINI_JEU/JOUER n’ont pas de clé restaurant. Si les règles varient entre restaurants, la structure relationnelle doit être complétée.
- La sélection de la période par semaine/mois/jour pour le top 10 doit être faite par une requête paramétrée sur `COMMANDE.date_commande` ; la vue livrée fournit le total historique.
- `MMM_ini.sql` détruit le schéma `mmm`. Il ne doit pas être utilisé pour mettre à niveau une base de données avec des données à garder.
