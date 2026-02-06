-- SQLite fulltext: clean stale entries
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
