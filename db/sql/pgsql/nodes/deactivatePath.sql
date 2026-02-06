UPDATE cms.urlpaths
SET
	inactive = now(),
	editor = :editor
WHERE
	path = :path
	AND locale = :locale;
