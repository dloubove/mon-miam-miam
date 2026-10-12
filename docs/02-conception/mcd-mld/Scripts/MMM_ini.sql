-- Réinitialisation complète du schéma applicatif.
-- ATTENTION : DROP SCHEMA supprime toutes les données mmm.
DROP SCHEMA IF EXISTS mmm CASCADE;
CREATE SCHEMA mmm;
SET search_path TO mmm, public;