-- Drop btree_gin extension (not needed for tsvector GIN indexes)
-- This extension was added in the initial migration but is not required.
-- Only drop if no other objects depend on it.
DROP EXTENSION IF EXISTS btree_gin;

-- Ensure remaining extensions are available (idempotent)
CREATE EXTENSION IF NOT EXISTS btree_gist;
CREATE EXTENSION IF NOT EXISTS unaccent;
