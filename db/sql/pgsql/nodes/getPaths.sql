SELECT
	path,
	locale,
	creator,
	editor,
	created,
	inactive
FROM
	cms.urlpaths
WHERE
	node = :node
	AND inactive IS NULL;
