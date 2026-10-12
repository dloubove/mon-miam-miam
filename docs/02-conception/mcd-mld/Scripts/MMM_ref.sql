-- Référentiels et paramètres initiaux.

SET search_path TO mmm, public;

INSERT INTO role(libelle_role) VALUES ('employe'),('gerant'),('administrateur') ON CONFLICT (libelle_role) DO NOTHING;

INSERT INTO categorie(libelle_categorie) VALUES ('plat'),('boisson'),('dessert') ON CONFLICT (libelle_categorie) DO NOTHING;

INSERT INTO mini_jeu(nom_mini_jeu,description_mini_jeu,points_maximum) VALUES
 ('Quiz du jour','Questions courtes sur la cuisine et le campus.',20),
 ('Memo repas','Retrouver les paires d’articles.',10)
ON CONFLICT (nom_mini_jeu) DO NOTHING;

-- Valeurs de fidélité proposées par le cahier des charges, modifiables par restaurant.

INSERT INTO restaurant(nom_restaurant,adresse_restaurant,telephone_restaurant)
SELECT 'ZEDUCSP@CE','Résidence La Terrasse, campus UCAC-ICAM de Yansoki, Douala','+237000000000'
WHERE NOT EXISTS (SELECT 1 FROM restaurant WHERE nom_restaurant='ZEDUCSP@CE');

INSERT INTO parametre(id_restaurant,cle_parametre,valeur_parametre,description_parametre)
SELECT r.id_restaurant,p.cle,p.valeur,p.description
FROM restaurant r
CROSS JOIN (VALUES
 ('points_par_1000_fcfa','1','Points gagnés par tranche de 1000 FCFA payés'),
 ('points_par_tranche_reduction','15','Nombre de points correspondant à une tranche de remise'),
 ('fcfa_pour_15_points','1000','Réduction en FCFA pour 15 points utilisés'),
 ('seuil_min_points_utilisation','15','Nombre minimal de points utilisables'),
 ('duree_validite_points_mois','12','Durée de validité indicative des points, en mois')
) AS p(cle,valeur,description)
WHERE r.nom_restaurant='ZEDUCSP@CE'
ON CONFLICT (id_restaurant,cle_parametre) DO NOTHING;
