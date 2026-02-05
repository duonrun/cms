-- Clean orphaned FTS5 entries
-- Deletes from FTS5 using rowids from orphaned index entries
DELETE FROM cms_fulltext_fts
WHERE rowid IN (
	SELECT idx.rowid
	FROM cms_fulltext_idx idx
	WHERE idx.node NOT IN (
		SELECT n.node
		FROM cms_nodes n
		WHERE n.deleted IS NULL
		  AND n.published = 1
	)
);
