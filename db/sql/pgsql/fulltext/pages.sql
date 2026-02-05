SELECT
	n.content,
	t.handle
FROM
	cms.nodes n
JOIN cms.types t
	ON t.type = n.type
WHERE
	n.deleted IS NULL
	AND n.published = true;
