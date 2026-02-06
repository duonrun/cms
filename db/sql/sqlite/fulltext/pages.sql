-- SQLite fulltext: list indexable pages
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
