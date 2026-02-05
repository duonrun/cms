-- Upsert fulltext index mapping
-- Creates or updates the mapping between (node, locale) and FTS5 rowid
-- Returns the rowid for use in FTS5 operations
INSERT INTO cms_fulltext_idx (node, locale)
VALUES (:node, :locale)
ON CONFLICT (node, locale) DO NOTHING
RETURNING rowid;
