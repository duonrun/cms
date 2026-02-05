-- SQLite fulltext: clean stale entries
-- Note: Full FTS5 implementation is in Step 7
DELETE FROM cms_fulltext ft
WHERE
	ft.node NOT IN (
		SELECT
			n.node
		FROM
			cms_nodes n
		WHERE
			n.deleted IS NULL
			AND n.published = 1
	);
