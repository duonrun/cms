SELECT
	up.path,
	up.locale
FROM
	cms_urlpaths up
WHERE
	up.node = :node
	AND up.inactive IS NULL;
