UPDATE
	cms_nodes
SET
	deleted = strftime('%Y-%m-%d %H:%M:%S', 'now')
WHERE
	uid = :uid;

UPDATE
	cms_urlpaths
SET
	inactive = strftime('%Y-%m-%d %H:%M:%S', 'now'),
	editor = :editor
WHERE node IN (
	SELECT n.node FROM cms_nodes n WHERE n.uid = :uid
);
