/* ========================================================================
   MMM_test-pos.sql
   Tests unitaires POSITIFS de Mon Miam Miam.
   À exécuter après MMM_jdd.sql.
   Les changements sont montrés dans le résultat, puis annulés par ROLLBACK.
   ======================================================================== */

SET search_path TO mmm, public;
BEGIN;

/* GROUPE 1 — Jeu de données et références */

-- POS-BD-01 : comptes, profils et restaurant de démonstration présents
DO $$
DECLARE n_etudiants integer; n_personnel integer; n_restaurant integer;
BEGIN
 SELECT count(*) INTO n_etudiants FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email IN ('dan.loubove@etudiant.test','nathan.bella@etudiant.test','abdul.kadir@etudiant.test');
 SELECT count(*) INTO n_personnel FROM personnel p JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email IN ('junior.mbongue@zeducsp.test','emma@zeducsp.test','christe@zeducsp.test');
 SELECT count(*) INTO n_restaurant FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 ASSERT n_etudiants=3, 'POS-BD-01 : trois étudiants du JDD attendus';
 ASSERT n_personnel=3, 'POS-BD-01 : trois personnels du JDD attendus';
 ASSERT n_restaurant=1, 'POS-BD-01 : restaurant ZEDUCSP@CE attendu';
 RAISE NOTICE 'POS-BD-01 PASS : restaurant, trois personnels et trois étudiants présents.';
END;
$$;

-- POS-BD-02 : le parrainage a été crédité à la première commande payée d'Abdul
DO $$
DECLARE ok boolean;
BEGIN
 SELECT EXISTS (
  SELECT 1 FROM etudiant filleul
  JOIN utilisateur uf ON uf.id_utilisateur=filleul.id_utilisateur
  JOIN etudiant parrain ON parrain.id_utilisateur=filleul.id_parrain
  JOIN utilisateur up ON up.id_utilisateur=parrain.id_utilisateur
  WHERE uf.email='abdul.kadir@etudiant.test'
    AND up.email='dan.loubove@etudiant.test'
    AND filleul.recompense_attribuee
    AND EXISTS (SELECT 1 FROM mouvement_points mp
                WHERE mp.id_etudiant=parrain.id_utilisateur AND mp.type_mouvement='gain_parrainage')
 ) INTO ok;
 ASSERT ok, 'POS-BD-02 : Abdul doit être le filleul récompensé de Dan';
 RAISE NOTICE 'POS-BD-02 PASS : parrainage Abdul vers Dan récompensé.';
END;
$$;

/* GROUPE 2 — Commande et fidélité */

-- POS-TRG-01 : 15 points donnent 1000 FCFA de remise et le prix de ligne est figé
DO $$
DECLARE uid integer; rid integer; aid integer; cid integer; prix numeric; remise numeric; net numeric; solde integer;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='abdul.kadir@etudiant.test';
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 SELECT id_article INTO aid FROM article WHERE id_restaurant=rid AND nom_article='Plat du jour';

 INSERT INTO mouvement_points(type_mouvement,nombre_points,id_etudiant)
 VALUES ('ajustement',15,uid);
 INSERT INTO commande(mode_service,heure_arrivee,points_utilises,commentaire_client,id_etudiant,id_restaurant)
 VALUES ('sur_place',localtime,15,'TEST_POSITIVE_TEMPORAIRE',uid,rid)
 RETURNING id_commande INTO cid;
 INSERT INTO contenir(id_commande,id_article,quantite,prix_applique) VALUES (cid,aid,1,1);
 UPDATE commande SET statut_commande='en_attente' WHERE id_commande=cid;

 SELECT prix_applique INTO prix FROM contenir WHERE id_commande=cid AND id_article=aid;
 SELECT reduction_points,montant_total INTO remise,net FROM commande WHERE id_commande=cid;
 SELECT solde_points INTO solde FROM etudiant WHERE id_utilisateur=uid;
 ASSERT prix=2500, 'POS-TRG-01 : le prix de ligne doit reprendre le prix article';
 ASSERT remise=1000 AND net=1500, 'POS-TRG-01 : remise et montant net attendus';
 ASSERT solde=2, 'POS-TRG-01 : 15 points doivent être débités';
 RAISE NOTICE 'POS-TRG-01 PASS : prix %, remise %, net %, solde %.',prix,remise,net,solde;
END;
$$;

-- POS-TRG-02 : le paiement complet crédite une seule fois les points d'achat
DO $$
DECLARE cid integer; uid integer; solde integer; n_mouvements integer;
BEGIN
 SELECT id_commande,id_etudiant INTO cid,uid FROM commande
 WHERE commentaire_client='TEST_POSITIVE_TEMPORAIRE';
 INSERT INTO paiement(montant_paye,mode_paiement,statut_paiement,id_commande)
 SELECT montant_total,'especes','reussi',id_commande FROM commande WHERE id_commande=cid;
 SELECT solde_points INTO solde FROM etudiant WHERE id_utilisateur=uid;
 SELECT count(*) INTO n_mouvements FROM mouvement_points
 WHERE id_commande=cid AND type_mouvement='gain_commande';
 ASSERT solde=3, 'POS-TRG-02 : 1500 FCFA réglés doivent ajouter 1 point au solde initial 2';
 ASSERT n_mouvements=1, 'POS-TRG-02 : un seul mouvement gain_commande attendu';
 RAISE NOTICE 'POS-TRG-02 PASS : paiement complet crédité une seule fois.';
END;
$$;

-- POS-TRG-03 : une partie valide crée un mouvement de fidélité
DO $$
DECLARE uid integer; mid integer; solde integer; n_mouvements integer;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='abdul.kadir@etudiant.test';
 SELECT id_mini_jeu INTO mid FROM mini_jeu WHERE nom_mini_jeu='Quiz du jour';
 INSERT INTO jouer(id_etudiant,id_mini_jeu,date_partie,score_partie,points_remportes)
 VALUES(uid,mid,clock_timestamp()::timestamp,5,2);
 SELECT solde_points INTO solde FROM etudiant WHERE id_utilisateur=uid;
 SELECT count(*) INTO n_mouvements FROM mouvement_points
 WHERE id_etudiant=uid AND type_mouvement='gain_mini_jeu' AND nombre_points=2;
 ASSERT solde=5, 'POS-TRG-03 : le solde doit inclure les deux points du mini-jeu';
 ASSERT n_mouvements>=1, 'POS-TRG-03 : mouvement gain_mini_jeu attendu';
 RAISE NOTICE 'POS-TRG-03 PASS : mini-jeu crédité, solde %.',solde;
END;
$$;

/* GROUPE 3 — Vues et requêtes */

-- POS-REQ-01 : le grand livre et le solde mémorisé sont cohérents
DO $$
DECLARE n_ecarts integer;
BEGIN
 SELECT count(*) INTO n_ecarts FROM v_solde_points WHERE solde_memoire<>solde_calcule;
 ASSERT n_ecarts=0, 'POS-REQ-01 : aucun écart de solde attendu';
 RAISE NOTICE 'POS-REQ-01 PASS : aucun écart entre soldes et mouvements.';
END;
$$;

-- POS-REQ-02 : les vues de statistiques et de parrainage sont consultables
DO $$
DECLARE n_referrals integer; n_ventes integer; n_top integer; n_jeux integer;
BEGIN
 SELECT count(*) INTO n_referrals FROM referrals;
 SELECT count(*) INTO n_ventes FROM v_ventes_journalieres;
 SELECT count(*) INTO n_top FROM v_top_etudiants;
 SELECT count(*) INTO n_jeux FROM v_classement_mini_jeux;
 ASSERT n_referrals>=1, 'POS-REQ-02 : vue referrals non vide attendue';
 ASSERT n_ventes>=1, 'POS-REQ-02 : vue ventes non vide attendue';
 ASSERT n_top>=3, 'POS-REQ-02 : étudiants du JDD attendus dans la vue top';
 ASSERT n_jeux>=1, 'POS-REQ-02 : classement mini-jeu non vide attendu';
 RAISE NOTICE 'POS-REQ-02 PASS : vues referrals, ventes, top étudiants et mini-jeux consultables.';
END;
$$;

-- Vérifier aussi les contraintes différées, puis afficher les valeurs temporaires.
SET CONSTRAINTS ALL IMMEDIATE;
SELECT 'PASS' AS resultat, u.nom AS etudiant, e.solde_points,
       c.id_commande, c.montant_total AS montant_net_fcfa,
       c.points_utilises, c.reduction_points,
       (SELECT nombre_points FROM mouvement_points mp
        WHERE mp.id_commande=c.id_commande AND mp.type_mouvement='utilisation') AS points_debites,
       (SELECT nombre_points FROM mouvement_points mp
        WHERE mp.id_commande=c.id_commande AND mp.type_mouvement='gain_commande') AS points_gagnes_commande,
       (SELECT max(points_remportes) FROM jouer j WHERE j.id_etudiant=e.id_utilisateur) AS points_gagnes_jeu
FROM commande c
JOIN etudiant e ON e.id_utilisateur=c.id_etudiant
JOIN utilisateur u ON u.id_utilisateur=e.id_utilisateur
WHERE c.commentaire_client='TEST_POSITIVE_TEMPORAIRE';
ROLLBACK;

DO $$ BEGIN RAISE NOTICE '=== Tous les tests POSITIFS sont terminés ==='; END; $$;
