-- Invariants métier qui nécessitent une lecture de plusieurs lignes.
SET search_path TO mmm, public;

-- Le grand livre est la source de vérité : chaque mouvement met à jour le solde.
CREATE OR REPLACE FUNCTION appliquer_mouvement_points() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 UPDATE etudiant SET solde_points=solde_points+NEW.nombre_points
 WHERE id_utilisateur=NEW.id_etudiant AND solde_points+NEW.nombre_points>=0;
 IF NOT FOUND THEN RAISE EXCEPTION 'Solde de points insuffisant ou étudiant introuvable'; END IF;
 RETURN NEW;
END $$;

CREATE TRIGGER tr_mouvement_maj_solde AFTER INSERT ON mouvement_points FOR EACH ROW EXECUTE FUNCTION appliquer_mouvement_points();
CREATE OR REPLACE FUNCTION proteger_solde_etudiant() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_OP='INSERT' AND NEW.solde_points<>0 AND pg_trigger_depth()=0 THEN
  RAISE EXCEPTION 'Initialiser un solde avec un mouvement ajustement, pas directement';
 END IF;
 IF TG_OP='UPDATE' AND NEW.solde_points IS DISTINCT FROM OLD.solde_points AND pg_trigger_depth()=0 THEN
  RAISE EXCEPTION 'Le solde étudiant est maintenu par MOUVEMENT_POINTS';
 END IF;
 RETURN NEW;
END $$;


CREATE TRIGGER tr_solde_etudiant_protege BEFORE INSERT OR UPDATE OF solde_points ON etudiant FOR EACH ROW EXECUTE FUNCTION proteger_solde_etudiant();
CREATE OR REPLACE FUNCTION mouvement_immuable() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'Le grand livre des points est immuable ; ajouter un mouvement correctif'; END $$;
CREATE TRIGGER tr_mouvement_immuable BEFORE UPDATE OR DELETE ON mouvement_points FOR EACH ROW EXECUTE FUNCTION mouvement_immuable();

-- Exclusivité étudiant/personnel et totalité utilisateur (vérifiée à la fin de transaction).
CREATE OR REPLACE FUNCTION verifier_sous_type_utilisateur() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE uid integer; n integer;
BEGIN
 IF TG_TABLE_NAME='utilisateur' THEN
  uid := COALESCE(NEW.id_utilisateur, OLD.id_utilisateur);
 ELSIF TG_OP='DELETE' THEN
  uid := OLD.id_utilisateur;
 ELSE
  uid := NEW.id_utilisateur;
 END IF;

 IF NOT EXISTS (SELECT 1 FROM utilisateur WHERE id_utilisateur=uid) THEN RETURN NULL; END IF;

 SELECT (EXISTS(SELECT 1 FROM etudiant WHERE id_utilisateur=uid)::int + EXISTS(SELECT 1 FROM personnel WHERE id_utilisateur=uid)::int) INTO n;

 IF n <> 1 THEN RAISE EXCEPTION 'Un utilisateur doit avoir exactement un sous-type (étudiant ou personnel), id=%', uid; END IF;
 RETURN NULL;
END $$;

CREATE CONSTRAINT TRIGGER ct_utilisateur_sous_type AFTER INSERT OR UPDATE ON utilisateur DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION verifier_sous_type_utilisateur();

CREATE CONSTRAINT TRIGGER ct_etudiant_sous_type AFTER INSERT OR UPDATE OR DELETE ON etudiant DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION verifier_sous_type_utilisateur();

CREATE CONSTRAINT TRIGGER ct_personnel_sous_type AFTER INSERT OR UPDATE OR DELETE ON personnel DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION verifier_sous_type_utilisateur();

CREATE OR REPLACE FUNCTION exclusivite_sous_types() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_TABLE_NAME='etudiant' AND EXISTS(SELECT 1 FROM personnel WHERE id_utilisateur=NEW.id_utilisateur) THEN RAISE EXCEPTION 'Utilisateur déjà enregistré comme personnel'; END IF;
 IF TG_TABLE_NAME='personnel' AND EXISTS(SELECT 1 FROM etudiant WHERE id_utilisateur=NEW.id_utilisateur) THEN RAISE EXCEPTION 'Utilisateur déjà enregistré comme étudiant'; END IF;
 RETURN NEW;
END $$;

CREATE TRIGGER tr_etudiant_exclusif BEFORE INSERT OR UPDATE ON etudiant FOR EACH ROW EXECUTE FUNCTION exclusivite_sous_types();

CREATE TRIGGER tr_personnel_exclusif BEFORE INSERT OR UPDATE ON personnel FOR EACH ROW EXECUTE FUNCTION exclusivite_sous_types();

-- Une commande qui quitte le panier doit avoir une ligne, y compris après modifications des articles.
CREATE OR REPLACE FUNCTION verifier_commande_non_vide() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE cid integer; st varchar;
BEGIN
 cid := CASE WHEN TG_TABLE_NAME='commande' THEN COALESCE(NEW.id_commande, OLD.id_commande) ELSE COALESCE(NEW.id_commande, OLD.id_commande) END;
 SELECT statut_commande INTO st FROM commande WHERE id_commande=cid;
 IF st IS NOT NULL AND st <> 'panier' AND NOT EXISTS(SELECT 1 FROM contenir WHERE id_commande=cid) THEN RAISE EXCEPTION 'Une commande hors panier doit contenir au moins un article (commande %)',cid; END IF;
 RETURN NULL;
END $$;

CREATE CONSTRAINT TRIGGER ct_commande_non_vide AFTER INSERT OR UPDATE ON commande DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION verifier_commande_non_vide();

CREATE CONSTRAINT TRIGGER ct_contenir_non_vide AFTER INSERT OR UPDATE OR DELETE ON contenir DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION verifier_commande_non_vide();

-- Le prix figé de la ligne est celui de l'article lors de sa création / modification.
CREATE OR REPLACE FUNCTION figer_prix_article() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE st varchar;
BEGIN

 IF TG_OP<>'INSERT' THEN
  SELECT statut_commande INTO st FROM commande WHERE id_commande=OLD.id_commande;
  IF st IS DISTINCT FROM 'panier' THEN RAISE EXCEPTION 'Les lignes d’une commande confirmée sont immuables'; END IF;
 END IF;

 SELECT prix_unitaire INTO NEW.prix_applique FROM article WHERE id_article=NEW.id_article;
 RETURN NEW;
END $$;

CREATE TRIGGER tr_contenir_prix BEFORE INSERT OR UPDATE ON contenir FOR EACH ROW EXECUTE FUNCTION figer_prix_article();
CREATE OR REPLACE FUNCTION interdire_suppression_ligne_confirmee() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE st varchar;
BEGIN
 IF pg_trigger_depth()>1 THEN RETURN OLD; END IF;
 SELECT statut_commande INTO st FROM commande WHERE id_commande=OLD.id_commande;
 IF st IS DISTINCT FROM 'panier' THEN RAISE EXCEPTION 'Les lignes d’une commande confirmée sont immuables'; END IF;
 RETURN OLD;
END $$;


CREATE TRIGGER tr_contenir_suppression BEFORE DELETE ON contenir FOR EACH ROW EXECUTE FUNCTION interdire_suppression_ligne_confirmee();

-- Les points expirés restent dans le grand livre et sont compensés par un mouvement négatif.
CREATE OR REPLACE FUNCTION expirer_points_etudiant(p_id_etudiant integer) RETURNS integer LANGUAGE plpgsql AS $$
DECLARE lot record; solde integer; plus_recents integer; restant integer; nb integer := 0;
BEGIN
 PERFORM 1 FROM etudiant WHERE id_utilisateur=p_id_etudiant FOR UPDATE;
 FOR lot IN
  SELECT id_mouvement,nombre_points,date_mouvement FROM mouvement_points
  WHERE id_etudiant=p_id_etudiant AND nombre_points>0 AND date_expiration_points<=current_date
    AND type_mouvement IN ('gain_commande','gain_parrainage','gain_evenement','gain_mini_jeu','ajustement')
  ORDER BY date_mouvement,id_mouvement FOR UPDATE
 LOOP
  SELECT solde_points INTO solde FROM etudiant WHERE id_utilisateur=p_id_etudiant;
  SELECT COALESCE(sum(nombre_points),0)::integer INTO plus_recents FROM mouvement_points
   WHERE id_etudiant=p_id_etudiant AND nombre_points>0
     AND (date_mouvement,id_mouvement)>(lot.date_mouvement,lot.id_mouvement);
  restant := GREATEST(0,LEAST(lot.nombre_points,solde-plus_recents));
  IF restant>0 THEN
   INSERT INTO mouvement_points(type_mouvement,nombre_points,date_mouvement,id_etudiant)
   VALUES ('expiration',-restant,current_timestamp,p_id_etudiant);
   nb := nb+1;
  END IF;
 END LOOP;
 RETURN nb;
END $$;

CREATE OR REPLACE FUNCTION traiter_expiration_points() RETURNS integer LANGUAGE plpgsql AS $$
DECLARE e record; total integer := 0;
BEGIN
 FOR e IN SELECT id_utilisateur FROM etudiant LOOP
  total := total + expirer_points_etudiant(e.id_utilisateur);
 END LOOP;
 RETURN total;
END $$;

-- Conversion de caisse : 15 points donnent 1000 FCFA de réduction.
CREATE OR REPLACE FUNCTION controler_points_commande() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE solde integer; seuil integer; points_tranche integer; montant_reduction numeric; sous_total numeric; taux_promo numeric := 0; remise_promo numeric := 0;
BEGIN
 IF TG_OP='INSERT' AND NEW.statut_commande <> 'panier' THEN RAISE EXCEPTION 'Créer la commande au statut panier, puis la confirmer après ajout des articles'; END IF;
 IF TG_OP='UPDATE' AND OLD.statut_commande <> 'panier' AND NEW.points_utilises IS DISTINCT FROM OLD.points_utilises THEN
  RAISE EXCEPTION 'Les points utilisés ne peuvent plus être modifiés après confirmation';
 END IF;
 IF TG_OP='UPDATE' AND OLD.statut_commande <> 'panier' AND
    (NEW.id_etudiant IS DISTINCT FROM OLD.id_etudiant OR NEW.id_restaurant IS DISTINCT FROM OLD.id_restaurant OR NEW.id_promotion IS DISTINCT FROM OLD.id_promotion) THEN
  RAISE EXCEPTION 'Le client, restaurant et promotion ne peuvent plus être modifiés après confirmation';
 END IF;
 IF TG_OP='UPDATE' AND OLD.statut_commande <> 'panier' AND NEW.statut_commande='panier' THEN
  RAISE EXCEPTION 'Une commande confirmée ne peut pas redevenir un panier';
 END IF;
 IF TG_OP='UPDATE' AND OLD.statut_commande='panier' AND NEW.statut_commande <> 'panier' THEN
  SELECT COALESCE(sum(quantite * prix_applique),0) INTO sous_total FROM contenir WHERE id_commande=NEW.id_commande;
  IF NEW.id_promotion IS NOT NULL THEN
   SELECT taux_reduction INTO taux_promo FROM promotion
   WHERE id_promotion=NEW.id_promotion AND current_date BETWEEN date_debut_promo AND date_fin_promo;
   IF taux_promo IS NULL THEN RAISE EXCEPTION 'La promotion est absente ou hors période de validité'; END IF;
   remise_promo := floor(sous_total * taux_promo / 100.0);
  END IF;
  SELECT valeur_parametre::integer INTO seuil FROM parametre WHERE id_restaurant=NEW.id_restaurant AND cle_parametre='seuil_min_points_utilisation';
  IF seuil IS NULL OR seuil <= 0 THEN RAISE EXCEPTION 'Paramètre seuil_min_points_utilisation absent ou invalide'; END IF;
  SELECT valeur_parametre::integer INTO points_tranche FROM parametre WHERE id_restaurant=NEW.id_restaurant AND cle_parametre='points_par_tranche_reduction';
  IF points_tranche IS NULL OR points_tranche <= 0 THEN RAISE EXCEPTION 'Paramètre points_par_tranche_reduction absent ou invalide'; END IF;
  IF NEW.points_utilises > 0 THEN
   IF NEW.points_utilises < seuil OR NEW.points_utilises % points_tranche <> 0 THEN RAISE EXCEPTION 'Le nombre de points doit atteindre le seuil (%) et être un multiple de la tranche (%)',seuil,points_tranche; END IF;
   PERFORM expirer_points_etudiant(NEW.id_etudiant);
   SELECT solde_points INTO solde FROM etudiant WHERE id_utilisateur=NEW.id_etudiant FOR UPDATE;
   IF NEW.points_utilises > solde THEN RAISE EXCEPTION 'Solde de points insuffisant : demandé %, disponible %',NEW.points_utilises,solde; END IF;
   SELECT (NEW.points_utilises / points_tranche) * valeur_parametre::numeric INTO montant_reduction
   FROM parametre WHERE id_restaurant=NEW.id_restaurant AND cle_parametre='fcfa_pour_15_points';
   IF montant_reduction IS NULL OR montant_reduction <= 0 THEN RAISE EXCEPTION 'Paramètre fcfa_pour_15_points absent ou invalide'; END IF;
   IF montant_reduction > sous_total-remise_promo THEN RAISE EXCEPTION 'La réduction en points dépasse le montant restant après promotion'; END IF;
  ELSE montant_reduction := 0;
  END IF;
  NEW.reduction_points := montant_reduction;
  NEW.montant_total := sous_total - remise_promo - montant_reduction;
 ELSE
  IF NEW.points_utilises=0 THEN NEW.reduction_points := 0; END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_commande_points BEFORE INSERT OR UPDATE OF statut_commande,points_utilises,id_etudiant,id_restaurant,id_promotion ON commande FOR EACH ROW EXECUTE FUNCTION controler_points_commande();

CREATE OR REPLACE FUNCTION debiter_points_commande() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF OLD.statut_commande='panier' AND NEW.statut_commande <> 'panier' AND NEW.points_utilises > 0 THEN
  INSERT INTO mouvement_points(type_mouvement,nombre_points,id_etudiant,id_commande)
  VALUES ('utilisation',-NEW.points_utilises,NEW.id_etudiant,NEW.id_commande);
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_commande_debit_points AFTER UPDATE OF statut_commande ON commande FOR EACH ROW EXECUTE FUNCTION debiter_points_commande();

-- Calcul du gain après règlement complet d'une commande. Idempotence assurée par l'index unique.
CREATE OR REPLACE FUNCTION crediter_commande_payee() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE c commande%ROWTYPE; total_regle numeric; nb_points integer; duree integer;
BEGIN
 IF NEW.statut_paiement='reussi' AND (TG_OP='INSERT' OR OLD.statut_paiement IS DISTINCT FROM 'reussi') THEN
  SELECT * INTO c FROM commande WHERE id_commande=NEW.id_commande FOR UPDATE;
  SELECT COALESCE(sum(montant_paye),0) INTO total_regle FROM paiement WHERE id_commande=c.id_commande AND statut_paiement='reussi';
  IF total_regle >= c.montant_total AND NOT EXISTS (SELECT 1 FROM mouvement_points WHERE id_commande=c.id_commande AND type_mouvement='gain_commande') THEN
   SELECT floor(c.montant_total / 1000.0) * valeur_parametre::numeric INTO nb_points
   FROM parametre WHERE id_restaurant=c.id_restaurant AND cle_parametre='points_par_1000_fcfa';
   SELECT valeur_parametre::integer INTO duree FROM parametre WHERE id_restaurant=c.id_restaurant AND cle_parametre='duree_validite_points_mois';
   IF nb_points IS NULL OR duree IS NULL OR duree <= 0 THEN RAISE EXCEPTION 'Paramètres de fidélité manquants ou invalides'; END IF;
   IF nb_points > 0 THEN
    INSERT INTO mouvement_points(type_mouvement,nombre_points,date_expiration_points,id_etudiant,id_commande)
    VALUES ('gain_commande',nb_points,(current_date + make_interval(months=>duree))::date,c.id_etudiant,c.id_commande);
   END IF;
  END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_paiement_gain_points AFTER INSERT OR UPDATE OF statut_paiement ON paiement FOR EACH ROW EXECUTE FUNCTION crediter_commande_payee();

-- Une récompense de parrainage est versée au parrain, une fois, au premier paiement réussi du filleul.
CREATE OR REPLACE FUNCTION recompenser_parrainage() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE filleul integer; parrain integer; p integer; c commande%ROWTYPE; total_regle numeric; duree integer;
BEGIN
 IF NEW.statut_paiement='reussi' AND (TG_OP='INSERT' OR OLD.statut_paiement IS DISTINCT FROM 'reussi') THEN
  SELECT * INTO c FROM commande WHERE id_commande=NEW.id_commande;
  SELECT COALESCE(sum(montant_paye),0) INTO total_regle FROM paiement WHERE id_commande=c.id_commande AND statut_paiement='reussi';
  IF total_regle < c.montant_total THEN RETURN NEW; END IF;
  filleul := c.id_etudiant;
  SELECT id_parrain, COALESCE(points_parrainage,0) INTO parrain,p FROM etudiant WHERE id_utilisateur=filleul AND NOT recompense_attribuee FOR UPDATE;
  IF parrain IS NOT NULL AND p>0 THEN
   SELECT valeur_parametre::integer INTO duree FROM parametre WHERE id_restaurant=c.id_restaurant AND cle_parametre='duree_validite_points_mois';
   UPDATE etudiant SET recompense_attribuee=true WHERE id_utilisateur=filleul;
   INSERT INTO mouvement_points(type_mouvement,nombre_points,date_expiration_points,id_etudiant,id_commande)
   VALUES ('gain_parrainage',p,(current_date + make_interval(months=>duree))::date,parrain,c.id_commande);
  END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_paiement_parrainage AFTER INSERT OR UPDATE OF statut_paiement ON paiement FOR EACH ROW EXECUTE FUNCTION recompenser_parrainage();

-- Jeton : passage à utilisé autorisé une seule fois, avant expiration.
CREATE OR REPLACE FUNCTION controler_jeton() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF NEW.jeton_utilise AND (TG_OP='INSERT' OR NOT OLD.jeton_utilise) THEN
  IF NEW.date_expiration_jeton <= current_timestamp THEN RAISE EXCEPTION 'Jeton expiré'; END IF;
 END IF;
 IF TG_OP='UPDATE' AND OLD.jeton_utilise AND NOT NEW.jeton_utilise THEN RAISE EXCEPTION 'Un jeton consommé ne peut pas être réactivé'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_jeton_usage BEFORE INSERT OR UPDATE ON jeton_mdp FOR EACH ROW EXECUTE FUNCTION controler_jeton();

-- Le mini-jeu doit être actif et le gain sous le plafond. Toute partie créditée crée un mouvement.
CREATE OR REPLACE FUNCTION valider_partie_mini_jeu() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE plafond integer; statut varchar;
BEGIN
 SELECT points_maximum,statut_mini_jeu INTO plafond,statut FROM mini_jeu WHERE id_mini_jeu=NEW.id_mini_jeu FOR SHARE;
 IF statut <> 'actif' THEN RAISE EXCEPTION 'Le mini-jeu est inactif'; END IF;
 IF NEW.points_remportes > plafond THEN RAISE EXCEPTION 'Le gain dépasse le plafond du mini-jeu'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_jouer_validation BEFORE INSERT OR UPDATE ON jouer FOR EACH ROW EXECUTE FUNCTION valider_partie_mini_jeu();
CREATE OR REPLACE FUNCTION crediter_partie_mini_jeu() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE duree integer;
BEGIN
 IF NEW.points_remportes > 0 THEN
  SELECT valeur_parametre::integer INTO duree FROM parametre WHERE cle_parametre='duree_validite_points_mois' ORDER BY id_restaurant LIMIT 1;
  IF duree IS NULL OR duree<=0 THEN RAISE EXCEPTION 'Paramètre durée de validité des points absent ou invalide'; END IF;
  INSERT INTO mouvement_points(type_mouvement,nombre_points,date_expiration_points,id_etudiant,date_mouvement)
  VALUES ('gain_mini_jeu',NEW.points_remportes,(current_date + make_interval(months=>duree))::date,NEW.id_etudiant,NEW.date_partie);
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_jouer_credit AFTER INSERT ON jouer FOR EACH ROW EXECUTE FUNCTION crediter_partie_mini_jeu();
CREATE OR REPLACE FUNCTION interdire_modification_partie() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'Une partie enregistrée est immuable ; une correction doit passer par un mouvement ajustement autorisé'; END $$;
CREATE TRIGGER tr_jouer_immuable BEFORE UPDATE OR DELETE ON jouer FOR EACH ROW EXECUTE FUNCTION interdire_modification_partie();
