-- Search fulltext index
-- Returns node IDs matching the search query
-- :query should be an FTS5 query string (e.g., "beer OR brewing")
SELECT
	idx.node,
	idx.locale,
	fts.rank
FROM cms_fulltext_fts fts
JOIN cms_fulltext_idx idx ON idx.rowid = fts.rowid
WHERE cms_fulltext_fts MATCH :query
ORDER BY fts.rank;
