-- Add FTS5 fulltext search tables for existing installations
-- New installations already have these tables from init-ddl.sql
-- This migration is idempotent - it only creates tables if they don't exist

-- Create a mapping table to link FTS rowid to (node, locale)
CREATE TABLE IF NOT EXISTS cms_fulltext_idx (
	rowid INTEGER PRIMARY KEY,
	node INTEGER NOT NULL,
	locale TEXT NOT NULL,
	CONSTRAINT uq_fulltext_idx UNIQUE (node, locale),
	CONSTRAINT fk_fulltext_idx_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node) ON DELETE CASCADE
);

-- For FTS5 virtual tables, check if it exists first via sqlite_master
-- This is handled by the application code since FTS5 doesn't support IF NOT EXISTS
