INSERT INTO cms_fulltext (node, locale, document)
SELECT
	n.node,
	'all',
	n.content
FROM
	cms_nodes n
WHERE
	n.deleted IS NULL
	AND n.published = 1;
