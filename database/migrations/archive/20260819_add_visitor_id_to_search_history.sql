-- Existing search history is intentionally discarded because it cannot be
-- assigned safely to an anonymous visitor.
TRUNCATE TABLE flight_searches;
TRUNCATE TABLE hotel_searches;

ALTER TABLE flight_searches
  ADD COLUMN visitor_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL AFTER id,
  ADD KEY idx_visitor_created (visitor_id, created_at, id);

ALTER TABLE hotel_searches
  ADD COLUMN visitor_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL AFTER id,
  ADD KEY idx_visitor_created (visitor_id, created_at, id);
