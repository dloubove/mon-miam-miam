-- Jeu de données de démonstration Mon Miam Miam.
-- À lancer après MMM_ini.sql, MMM_cre.sql, MMM_inv.sql et MMM_ref.sql.
-- Une transaction est nécessaire : chaque UTILISATEUR doit avoir son sous-type.
SET search_path TO mmm, public;
BEGIN;

-- Ces mots de passe sont des marqueurs de démonstration, non utilisables pour une connexion.
-- Les remplacer par des hashes BCrypt générés par l'application si l'authentification est testée.
INSERT INTO utilisateur(nom,email,mot_de_passe,telephone) VALUES
 ('Junior Mbongue','junior.mbongue@zeducsp.test','HASH_DEMO_A_REMPLACER','+237690000001'),
 ('Christe','christe@zeducsp.test','HASH_DEMO_A_REMPLACER','+237690000002'),
 ('Emma','emma@zeducsp.test','HASH_DEMO_A_REMPLACER','+237690000003'),
 ('Dan LOUBOVE','dan.loubove@etudiant.test','HASH_DEMO_A_REMPLACER','+237690000004'),
 ('Nathan Bella','nathan.bella@etudiant.test','HASH_DEMO_A_REMPLACER','+237690000005'),
 ('Abdul Kadir','abdul.kadir@etudiant.test','HASH_DEMO_A_REMPLACER','+237690000006')
ON CONFLICT (email) DO NOTHING;

-- Le modèle ferme les rôles à employe, gerant et administrateur : le propriétaire reçoit administrateur.
INSERT INTO personnel(id_utilisateur,matricule,date_embauche,id_role,id_restaurant,id_createur)
SELECT u.id_utilisateur,'PROP-001',current_date,r.id_role,rest.id_restaurant,NULL
FROM utilisateur u CROSS JOIN role r CROSS JOIN restaurant rest
WHERE u.email='junior.mbongue@zeducsp.test' AND r.libelle_role='administrateur' AND rest.nom_restaurant='ZEDUCSP@CE'
ON CONFLICT (id_utilisateur) DO NOTHING;

INSERT INTO personnel(id_utilisateur,matricule,date_embauche,id_role,id_restaurant,id_createur)
SELECT u.id_utilisateur,'GER-001',current_date,r.id_role,rest.id_restaurant,j.id_utilisateur
FROM utilisateur u CROSS JOIN role r CROSS JOIN restaurant rest
CROSS JOIN personnel j
WHERE u.email='emma@zeducsp.test' AND r.libelle_role='gerant' AND rest.nom_restaurant='ZEDUCSP@CE'
  AND j.matricule='PROP-001'
ON CONFLICT (id_utilisateur) DO NOTHING;

INSERT INTO personnel(id_utilisateur,matricule,date_embauche,id_role,id_restaurant,id_createur)
SELECT u.id_utilisateur,'EMP-001',current_date,r.id_role,rest.id_restaurant,e.id_utilisateur
FROM utilisateur u CROSS JOIN role r CROSS JOIN restaurant rest
CROSS JOIN personnel e
WHERE u.email='christe@zeducsp.test' AND r.libelle_role='employe' AND rest.nom_restaurant='ZEDUCSP@CE'
  AND e.matricule='GER-001'
ON CONFLICT (id_utilisateur) DO NOTHING;

INSERT INTO etudiant(id_utilisateur,code_parrainage)
SELECT id_utilisateur,code FROM utilisateur u
JOIN (VALUES
 ('dan.loubove@etudiant.test','DAN2026'),
 ('nathan.bella@etudiant.test','NATHAN2026'),
 ('abdul.kadir@etudiant.test','ABDUL2026')
) AS s(email,code) USING (email)
ON CONFLICT (id_utilisateur) DO NOTHING;

-- Dan parraine Abdul. La récompense de démonstration est de 5 points, portée par le profil du filleul.
UPDATE etudiant abd
SET id_parrain=dan.id_utilisateur, date_parrainage=current_timestamp,
    recompense_attribuee=false, points_parrainage=5
FROM etudiant dan
JOIN utilisateur udan ON udan.id_utilisateur=dan.id_utilisateur
JOIN utilisateur uabd ON uabd.email='abdul.kadir@etudiant.test'
WHERE udan.email='dan.loubove@etudiant.test'
  AND abd.id_utilisateur=uabd.id_utilisateur
  AND abd.id_parrain IS NULL;

-- Carte et menu de démonstration du restaurant.
INSERT INTO article(nom_article,description_article,prix_unitaire,disponible,id_categorie,id_restaurant)
SELECT 'Plat du jour','Article de démonstration pour les commandes et les points',2500,true,c.id_categorie,r.id_restaurant
FROM categorie c CROSS JOIN restaurant r
WHERE c.libelle_categorie='plat' AND r.nom_restaurant='ZEDUCSP@CE'
  AND NOT EXISTS (SELECT 1 FROM article a WHERE a.nom_article='Plat du jour' AND a.id_restaurant=r.id_restaurant);

INSERT INTO article(nom_article,description_article,prix_unitaire,disponible,id_categorie,id_restaurant)
SELECT 'Jus de démonstration','Boisson de démonstration',1000,true,c.id_categorie,r.id_restaurant
FROM categorie c CROSS JOIN restaurant r
WHERE c.libelle_categorie='boisson' AND r.nom_restaurant='ZEDUCSP@CE'
  AND NOT EXISTS (SELECT 1 FROM article a WHERE a.nom_article='Jus de démonstration' AND a.id_restaurant=r.id_restaurant);

-- Carte demandée pour ZEDUCSP@CE. Les suppléments sont des articles distincts
-- afin qu'ils puissent être sélectionnés et tarifés dans le panier.
WITH produits(nom, description, prix, categorie) AS (VALUES
 ('Jus de fruits','Jus de fruits',500,'boisson'),
 ('Riz Bolognaise','Riz accompagné de sauce bolognaise',1000,'plat'),
 ('Poulet pané','Poulet pané',2000,'plat'),
 ('Frites de plantains','Supplément : frites de plantains',500,'plat'),
 ('Frites de pommes','Supplément : frites de pommes de terre',500,'plat'),
 ('Jus Spécial Pamplemousse','Jus spécial au pamplemousse',600,'boisson'),
 ('Okok avec bâtons','Okok accompagné de bâtons',1000,'plat'),
 ('Supplément bâtons (unité)','Bâton supplémentaire, prix à l’unité',100,'plat'),
 ('Eru','Eru',1000,'plat'),
 ('Poulet mayo','Poulet à la mayonnaise',2500,'plat'),
 ('Rôti de porc','Rôti de porc',1300,'plat'),
 ('Rôti de poulet','Rôti de poulet',1300,'plat'),
 ('Poisson Braisé','Poisson braisé',2000,'plat'),
 ('Poulet braisé','Poulet braisé',2000,'plat'),
 ('Booster mangue-Orange','Boisson Booster mangue-orange',850,'boisson'),
 ('Booster Cola','Boisson Booster Cola',850,'boisson'),
 ('Jus Planet','Jus Planet',500,'boisson')
)
INSERT INTO article(nom_article,description_article,prix_unitaire,disponible,id_categorie,id_restaurant)
SELECT p.nom,p.description,p.prix,true,c.id_categorie,r.id_restaurant
FROM produits p
JOIN categorie c ON c.libelle_categorie=p.categorie
CROSS JOIN restaurant r
WHERE r.nom_restaurant='ZEDUCSP@CE'
  AND NOT EXISTS (
    SELECT 1 FROM article a
    WHERE a.nom_article=p.nom AND a.id_restaurant=r.id_restaurant
  );

INSERT INTO menu_jour(date_menu,id_restaurant)
SELECT current_date,id_restaurant FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE'
ON CONFLICT (id_restaurant,date_menu) DO NOTHING;
INSERT INTO composer(id_menu_jour,id_article)
SELECT m.id_menu_jour,a.id_article FROM menu_jour m
JOIN restaurant r ON r.id_restaurant=m.id_restaurant
JOIN article a ON a.id_restaurant=r.id_restaurant
WHERE r.nom_restaurant='ZEDUCSP@CE' AND m.date_menu=current_date
  AND a.disponible
ON CONFLICT (id_menu_jour,id_article) DO NOTHING;

-- Première commande payée d'Abdul pour vérifier les gains de commande et de parrainage.
DO $$
DECLARE uid integer; rid integer; aid integer; cid integer;
BEGIN
 SELECT id_utilisateur INTO uid FROM utilisateur WHERE email='abdul.kadir@etudiant.test';
 SELECT id_restaurant INTO rid FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE';
 SELECT id_article INTO aid FROM article WHERE nom_article='Plat du jour' AND id_restaurant=rid;
 IF NOT EXISTS (SELECT 1 FROM paiement WHERE reference_transaction='JDD-ABDUL-PREMIERE-COMMANDE') THEN
  INSERT INTO commande(mode_service,heure_arrivee,id_etudiant,id_restaurant)
  VALUES ('sur_place',localtime,uid,rid) RETURNING id_commande INTO cid;
  INSERT INTO contenir(id_commande,id_article,quantite,prix_applique) VALUES (cid,aid,1,1);
  UPDATE commande SET statut_commande='en_attente' WHERE id_commande=cid;
  INSERT INTO paiement(montant_paye,mode_paiement,reference_transaction,statut_paiement,id_commande)
  SELECT montant_total,'especes','JDD-ABDUL-PREMIERE-COMMANDE','reussi',id_commande
  FROM commande WHERE id_commande=cid;
 END IF;
END $$;

COMMIT;
