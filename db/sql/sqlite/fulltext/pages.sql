-- SQLite fulltext: list indexable pages
-- Note: Full FTS5 implementation is in Step 7
SELECT
	n.content,
	t.handle
FROM
	cms_nodes n
JOIN cms_types t
	ON t.type = n.type
WHERE
	n.deleted IS NULL
	AND n.published = 1;
