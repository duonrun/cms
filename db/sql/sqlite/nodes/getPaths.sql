SELECT
	path,
	locale,
	creator,
	editor,
	created,
	inactive
FROM
	cms_urlpaths
WHERE
	node = :node
	AND inactive IS NULL;
