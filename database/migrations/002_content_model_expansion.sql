-- 002_content_model_expansion.sql
--
-- Adds the columns needed for living artists, galleries/studios, era
-- tagging, and recurring-tradition coverage. Purely additive — no drops,
-- no data loss — safe to run against an already-populated database.
--
-- Apply once to every environment that isn't freshly installed from
-- schema.sql (schema.sql itself already carries these columns for new
-- installs — see that file's entities/stories CREATE TABLE statements).

ALTER TABLE entities
  ADD COLUMN era_status ENUM('historical','contemporary','both')
      NOT NULL DEFAULT 'historical' AFTER type,
  ADD COLUMN place_category ENUM('historic_site','gallery_studio')
      NULL AFTER coordinates,
  ADD COLUMN is_recurring TINYINT(1) NOT NULL DEFAULT 0 AFTER cadence,
  ADD COLUMN medium VARCHAR(255) NULL AFTER is_recurring,
  ADD COLUMN gallery_affiliation VARCHAR(255) NULL AFTER medium,
  ADD COLUMN website_url VARCHAR(500) NULL AFTER gallery_affiliation,
  ADD COLUMN instagram_url VARCHAR(500) NULL AFTER website_url,
  ADD INDEX (era_status),
  ADD INDEX (place_category);

-- Existing place rows have no meaningful default other than "historic" —
-- backfill explicitly rather than leaving them NULL.
UPDATE entities SET place_category = 'historic_site' WHERE type = 'place';

ALTER TABLE stories
  ADD COLUMN coverage_year YEAR NULL AFTER spark_source;
