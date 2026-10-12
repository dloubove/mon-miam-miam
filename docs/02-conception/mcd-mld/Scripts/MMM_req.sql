-- Vues et requêtes usuelles d'administration/statistiques.
SET search_path TO mmm, public;

CREATE OR REPLACE VIEW v_solde_points AS
 SELECT e.id_utilisateur AS id_etudiant, e.code_parrainage, e.solde_points AS solde_memoire,
        COALESCE(sum(mp.nombre_points),0)::integer AS solde_calcule
 FROM etudiant e LEFT JOIN mouvement_points mp ON mp.id_etudiant=e.id_utilisateur
 GROUP BY e.id_utilisateur,e.code_parrainage,e.solde_points;


CREATE OR REPLACE VIEW v_ventes_journalieres AS
 SELECT c.id_restaurant, c.date_commande::date AS jour, count(DISTINCT c.id_commande) AS commandes,
        sum(p.montant_paye) FILTER (WHERE p.statut_paiement='reussi') AS total_encaisse_fcfa
 FROM commande c LEFT JOIN paiement p ON p.id_commande=c.id_commande
 WHERE c.statut_commande NOT IN ('panier','annulee')
 GROUP BY c.id_restaurant,c.date_commande::date;


CREATE OR REPLACE VIEW v_top_etudiants AS
 SELECT e.id_utilisateur, u.nom, e.solde_points,
        count(c.id_commande) FILTER (WHERE c.statut_commande IN ('servie','livree')) AS commandes_terminees
 FROM etudiant e JOIN utilisateur u USING(id_utilisateur) LEFT JOIN commande c ON c.id_etudiant=e.id_utilisateur
 GROUP BY e.id_utilisateur,u.nom,e.solde_points;


CREATE OR REPLACE VIEW v_classement_mini_jeux AS
 SELECT j.id_mini_jeu,m.nom_mini_jeu,j.id_etudiant,count(*) AS parties,
        max(j.score_partie) AS meilleur_score,sum(j.points_remportes) AS points_remportes
 FROM jouer j JOIN mini_jeu m USING(id_mini_jeu)
 GROUP BY j.id_mini_jeu,m.nom_mini_jeu,j.id_etudiant;


-- Parrainages sous forme de vue (aucune table supplémentaire n'est requise).
CREATE OR REPLACE VIEW referrals AS
 SELECT filleul.id_utilisateur AS id_filleul, filleul.code_parrainage AS code_filleul,
        parrain.id_utilisateur AS id_parrain, parrain.code_parrainage AS code_parrain,
        filleul.date_parrainage, filleul.recompense_attribuee
 FROM etudiant filleul LEFT JOIN etudiant parrain ON parrain.id_utilisateur=filleul.id_parrain;
