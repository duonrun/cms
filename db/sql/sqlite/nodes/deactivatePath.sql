UPDATE cms_urlpaths
SET
	inactive = strftime('%Y-%m-%d %H:%M:%S', 'now'),
	editor = :editor
WHERE
	path = :path
	AND locale = :locale;
