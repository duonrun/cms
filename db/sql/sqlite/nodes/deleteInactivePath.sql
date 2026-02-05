DELETE FROM cms_urlpaths
WHERE
	path = :path
	AND inactive IS NOT NULL;
