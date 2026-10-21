/* ========================================================================
   MMM_test-neg.sql
   Tests unitaires NÉGATIFS de Mon Miam Miam.
   À exécuter après MMM_jdd.sql.
   Chaque test attend un code d'erreur/règle précis ; une opération acceptée
   à tort déclenche un FAIL non intercepté.
   ======================================================================== */

SET search_path TO mmm, public;

/* GROUPE 1 — Domaines et contraintes CHECK */

-- NEG-BD-01 : nom utilisateur vide
DO $$ DECLARE rejete boolean := false;
BEGIN
 BEGIN
  INSERT INTO utilisateur(nom,email,mot_de_passe,telephone)
  VALUES ('   ','neg01@monmiam.test','hash','+237690000101');
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-01 FAIL : nom vide accepté'; END IF;
 RAISE NOTICE 'NEG-BD-01 PASS : nom vide rejeté par le domaine nom_long.';
END $$;

-- NEG-BD-02 : adresse email invalide
DO $$ DECLARE rejete boolean := false;
BEGIN
 BEGIN
  INSERT INTO utilisateur(nom,email,mot_de_passe,telephone)
  VALUES ('Test email','adresse-invalide','hash','+237690000102');
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-02 FAIL : email invalide accepté'; END IF;
 RAISE NOTICE 'NEG-BD-02 PASS : email invalide rejeté.';
END $$;

-- NEG-BD-03 : téléphone hors format autorisé
DO $$ DECLARE rejete boolean := false;
BEGIN
 BEGIN
  INSERT INTO utilisateur(nom,email,mot_de_passe,telephone)
  VALUES ('Test téléphone','neg03@monmiam.test','hash','ABCDE');
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-03 FAIL : téléphone invalide accepté'; END IF;
 RAISE NOTICE 'NEG-BD-03 PASS : téléphone invalide rejeté.';
END $$;

-- NEG-BD-04 : prix nul
DO $$ DECLARE rid integer; cid integer; rejete boolean := false;
BEGIN
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 SELECT id_categorie INTO cid FROM categorie WHERE libelle_categorie='plat';
 BEGIN
  INSERT INTO article(nom_article,prix_unitaire,id_categorie,id_restaurant)
  VALUES ('Article négatif prix',0,cid,rid);
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-04 FAIL : prix nul accepté'; END IF;
 RAISE NOTICE 'NEG-BD-04 PASS : prix d’article nul rejeté.';
END $$;

-- NEG-BD-05 : livraison sans adresse
DO $$ DECLARE uid integer; rid integer; rejete boolean := false;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 BEGIN
  INSERT INTO commande(mode_service,id_etudiant,id_restaurant)
  VALUES ('livraison',uid,rid);
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-05 FAIL : livraison sans adresse acceptée'; END IF;
 RAISE NOTICE 'NEG-BD-05 PASS : commande livraison sans adresse rejetée.';
END $$;

-- NEG-BD-06 : un étudiant ne peut pas se parrainer lui-même
DO $$ DECLARE uid integer; rejete boolean := false;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='dan.loubove@etudiant.test';
 BEGIN
  UPDATE etudiant SET id_parrain=uid,date_parrainage=current_timestamp WHERE id_utilisateur=uid;
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-06 FAIL : auto-parrainage accepté'; END IF;
 RAISE NOTICE 'NEG-BD-06 PASS : auto-parrainage rejeté.';
END $$;

-- NEG-BD-07 : mouvement de points nul interdit
DO $$ DECLARE uid integer; rejete boolean := false;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 BEGIN
  INSERT INTO mouvement_points(type_mouvement,nombre_points,id_etudiant)
  VALUES ('ajustement',0,uid);
 EXCEPTION WHEN check_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-BD-07 FAIL : mouvement nul accepté'; END IF;
 RAISE NOTICE 'NEG-BD-07 PASS : mouvement de zéro point rejeté.';
END $$;

/* GROUPE 2 — Clés étrangères et unicité */

-- NEG-FK-01 : article lié à une catégorie inexistante
DO $$ DECLARE rid integer; rejete boolean := false;
BEGIN
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 BEGIN
  INSERT INTO article(nom_article,prix_unitaire,id_categorie,id_restaurant)
  VALUES ('Article catégorie absente',1000,2147483000,rid);
 EXCEPTION WHEN foreign_key_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-FK-01 FAIL : catégorie inexistante acceptée'; END IF;
 RAISE NOTICE 'NEG-FK-01 PASS : clé étrangère catégorie rejetée.';
END $$;

-- NEG-FK-02 : paiement lié à une commande inexistante
DO $$ DECLARE rejete boolean := false;
BEGIN
 BEGIN
  INSERT INTO paiement(montant_paye,mode_paiement,id_commande)
  VALUES (1000,'especes',2147483000);
 EXCEPTION WHEN foreign_key_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-FK-02 FAIL : commande inexistante acceptée'; END IF;
 RAISE NOTICE 'NEG-FK-02 PASS : paiement sans commande rejeté.';
END $$;

-- NEG-UNQ-01 : email déjà utilisé
DO $$ DECLARE rejete boolean := false;
BEGIN
 BEGIN
  INSERT INTO utilisateur(nom,email,mot_de_passe,telephone)
  VALUES ('Doublon','dan.loubove@etudiant.test','hash','+237690000111');
 EXCEPTION WHEN unique_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-UNQ-01 FAIL : email dupliqué accepté'; END IF;
 RAISE NOTICE 'NEG-UNQ-01 PASS : email en double rejeté.';
END $$;

-- NEG-UNQ-02 : un restaurant ne peut avoir deux menus à la même date
DO $$ DECLARE rid integer; rejete boolean := false;
BEGIN
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 BEGIN
  INSERT INTO menu_jour(date_menu,id_restaurant) VALUES (current_date,rid);
 EXCEPTION WHEN unique_violation THEN rejete := true;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-UNQ-02 FAIL : menu dupliqué accepté'; END IF;
 RAISE NOTICE 'NEG-UNQ-02 PASS : deuxième menu du jour rejeté.';
END $$;

/* GROUPE 3 — Déclencheurs métier */

-- NEG-TRG-01 : un compte ne peut pas être à la fois personnel et étudiant
DO $$ DECLARE uid integer; rejete boolean := false; msg text;
BEGIN
 SELECT p.id_utilisateur INTO uid FROM personnel p JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='junior.mbongue@zeducsp.test';
 BEGIN
  INSERT INTO etudiant(id_utilisateur,code_parrainage) VALUES (uid,'NEG-SOUS-TYPE-01');
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg='Utilisateur déjà enregistré comme personnel' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-01 FAIL : double sous-type accepté'; END IF;
 RAISE NOTICE 'NEG-TRG-01 PASS : compte déjà personnel refusé comme étudiant.';
END $$;

-- NEG-TRG-02 : commande confirmée sans ligne article
DO $$ DECLARE uid integer; rid integer; cid integer; rejete boolean := false; msg text;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 BEGIN
  INSERT INTO commande(mode_service,heure_arrivee,id_etudiant,id_restaurant)
  VALUES ('sur_place',localtime,uid,rid) RETURNING id_commande INTO cid;
  UPDATE commande SET statut_commande='en_attente' WHERE id_commande=cid;
  SET CONSTRAINTS ct_commande_non_vide IMMEDIATE;
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg LIKE 'Une commande hors panier doit contenir%' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-02 FAIL : commande vide confirmée'; END IF;
 RAISE NOTICE 'NEG-TRG-02 PASS : confirmation d’une commande sans article rejetée.';
END $$;

-- NEG-TRG-03 : utiliser plus de points que le solde disponible
DO $$ DECLARE uid integer; rid integer; aid integer; cid integer; rejete boolean := false; msg text;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 SELECT id_article INTO aid FROM article WHERE nom_article='Plat du jour' AND id_restaurant=rid;
 BEGIN
  INSERT INTO commande(mode_service,heure_arrivee,id_etudiant,id_restaurant)
  VALUES ('sur_place',localtime,uid,rid) RETURNING id_commande INTO cid;
  INSERT INTO contenir(id_commande,id_article,quantite,prix_applique) VALUES (cid,aid,1,1);
  UPDATE commande SET points_utilises=15,statut_commande='en_attente' WHERE id_commande=cid;
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg LIKE 'Solde de points insuffisant%' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-03 FAIL : solde de points insuffisant accepté'; END IF;
 RAISE NOTICE 'NEG-TRG-03 PASS : dépense supérieure au solde rejetée.';
END $$;

-- NEG-TRG-04 : partie dans un mini-jeu inactif
DO $$ DECLARE uid integer; mid integer; rejete boolean := false; msg text;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 BEGIN
  INSERT INTO mini_jeu(nom_mini_jeu,points_maximum,statut_mini_jeu)
  VALUES ('NEG-Jeu inactif',5,'inactif') RETURNING id_mini_jeu INTO mid;
  INSERT INTO jouer(id_etudiant,id_mini_jeu,date_partie,score_partie,points_remportes)
  VALUES (uid,mid,clock_timestamp()::timestamp,1,1);
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg='Le mini-jeu est inactif' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-04 FAIL : jeu inactif accepté'; END IF;
 RAISE NOTICE 'NEG-TRG-04 PASS : partie sur jeu inactif rejetée.';
END $$;

-- NEG-TRG-05 : gain supérieur au plafond du mini-jeu
DO $$ DECLARE uid integer; mid integer; rejete boolean := false; msg text;
BEGIN
 SELECT e.id_utilisateur INTO uid FROM etudiant e JOIN utilisateur u USING(id_utilisateur)
 WHERE u.email='nathan.bella@etudiant.test';
 SELECT id_mini_jeu INTO mid FROM mini_jeu WHERE nom_mini_jeu='Quiz du jour';
 BEGIN
  INSERT INTO jouer(id_etudiant,id_mini_jeu,date_partie,score_partie,points_remportes)
  VALUES (uid,mid,clock_timestamp()::timestamp,1,999);
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg='Le gain dépasse le plafond du mini-jeu' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-05 FAIL : gain au-dessus du plafond accepté'; END IF;
 RAISE NOTICE 'NEG-TRG-05 PASS : gain de mini-jeu trop élevé rejeté.';
END $$;

-- NEG-TRG-06 : jeton déjà expiré marqué comme consommé
DO $$ DECLARE uid integer; rejete boolean := false; msg text;
BEGIN
 SELECT id_utilisateur INTO uid FROM utilisateur WHERE email='nathan.bella@etudiant.test';
 BEGIN
  INSERT INTO jeton_mdp(jeton_hash,date_expiration_jeton,jeton_utilise,id_utilisateur)
  VALUES ('NEG-JETON-EXPIRE',current_timestamp - interval '1 day',true,uid);
 EXCEPTION WHEN raise_exception THEN
  GET STACKED DIAGNOSTICS msg = MESSAGE_TEXT;
  IF msg='Jeton expiré' THEN rejete := true; ELSE RAISE; END IF;
 END;
 IF NOT rejete THEN RAISE EXCEPTION 'NEG-TRG-06 FAIL : jeton expiré consommé'; END IF;
 RAISE NOTICE 'NEG-TRG-06 PASS : jeton expiré rejeté.';
END $$;

DO $$ BEGIN RAISE NOTICE '=== Tous les tests NÉGATIFS sont terminés ==='; END; $$;
