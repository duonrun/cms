DELETE FROM cms.urlpaths
WHERE
	path = :path
	AND inactive IS NOT NULL;
