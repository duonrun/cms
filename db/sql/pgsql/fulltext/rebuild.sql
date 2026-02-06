INSERT INTO cms.fulltext (node, locale, document)
SELECT
	n.node,
	'all',
	to_tsvector('simple', n.content::text)
FROM
	cms.nodes n
WHERE
	n.deleted IS NULL
	AND n.published = true;
