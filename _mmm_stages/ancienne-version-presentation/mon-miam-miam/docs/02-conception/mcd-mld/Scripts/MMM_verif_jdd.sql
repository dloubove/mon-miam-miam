-- Requêtes de contrôle à lancer après MMM_jdd.sql.
-- Elles affichent les valeurs persistantes du jeu de données de démonstration.

-- 1. Restaurant et profils du personnel.
SELECT r.nom_restaurant, u.nom AS personne, ro.libelle_role,
       p.matricule, u.email, u.statut_compte
FROM mmm.personnel p
JOIN mmm.utilisateur u USING (id_utilisateur)
JOIN mmm.role ro USING (id_role)
JOIN mmm.restaurant r USING (id_restaurant)
WHERE r.nom_restaurant='ZEDUCSP@CE'
ORDER BY CASE ro.libelle_role WHEN 'administrateur' THEN 1 WHEN 'gerant' THEN 2 ELSE 3 END;

-- 2. Étudiants, soldes, parrain et état de récompense.
SELECT u.nom AS etudiant, u.email, u.statut_compte,
       e.code_parrainage, e.solde_points,
       parrain.nom AS parrain, e.date_parrainage,
       e.recompense_attribuee, e.points_parrainage
FROM mmm.etudiant e
JOIN mmm.utilisateur u ON u.id_utilisateur=e.id_utilisateur
LEFT JOIN mmm.etudiant ep ON ep.id_utilisateur=e.id_parrain
LEFT JOIN mmm.utilisateur parrain ON parrain.id_utilisateur=ep.id_utilisateur
ORDER BY u.nom;

-- 3. Commandes, lignes, paiements réussis et restaurant.
SELECT c.id_commande, u.nom AS etudiant, r.nom_restaurant,
       c.date_commande, c.statut_commande, c.mode_service,
       count(DISTINCT co.id_article) AS nombre_articles,
       c.points_utilises, c.reduction_points, c.montant_total,
       COALESCE(pa.montant_regle,0) AS montant_regle
FROM mmm.commande c
JOIN mmm.utilisateur u ON u.id_utilisateur=c.id_etudiant
JOIN mmm.restaurant r ON r.id_restaurant=c.id_restaurant
LEFT JOIN mmm.contenir co ON co.id_commande=c.id_commande
LEFT JOIN (
 SELECT id_commande,sum(montant_paye) FILTER (WHERE statut_paiement='reussi') AS montant_regle
 FROM mmm.paiement GROUP BY id_commande
) pa ON pa.id_commande=c.id_commande
WHERE r.nom_restaurant='ZEDUCSP@CE'
GROUP BY c.id_commande,u.nom,r.nom_restaurant,pa.montant_regle
ORDER BY c.date_commande DESC;

-- 4. Grand livre de points : lignes conservées pour audit.
SELECT u.nom AS etudiant, mp.date_mouvement, mp.type_mouvement,
       mp.nombre_points, mp.date_expiration_points, mp.id_commande
FROM mmm.mouvement_points mp
JOIN mmm.utilisateur u ON u.id_utilisateur=mp.id_etudiant
ORDER BY mp.date_mouvement,mp.id_mouvement;

-- 5. Vérification de cohérence : cette requête doit renvoyer zéro ligne.
SELECT * FROM mmm.v_solde_points
WHERE solde_memoire<>solde_calcule;
