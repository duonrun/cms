UPDATE
	cms_nodes
SET
	deleted = strftime('%Y-%m-%d %H:%M:%f', 'now'),
	changed = strftime('%Y-%m-%d %H:%M:%f', 'now')
WHERE
	uid = :uid;

UPDATE
	cms_urlpaths
SET
	inactive = strftime('%Y-%m-%d %H:%M:%f', 'now'),
	editor = :editor
WHERE node IN (
	SELECT n.node FROM cms_nodes n WHERE n.uid = :uid
);
