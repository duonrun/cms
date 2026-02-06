INSERT INTO cms_nodes (
	uid,
	type,
	published,
	locked,
	hidden,
	editor,
	creator,
	content
)
SELECT
	:uid,
	type,
	:published,
	:locked,
	:hidden,
	:editor,
	:editor,
	:content
FROM
	cms_types t
WHERE
	t.handle = :type

ON CONFLICT (uid) DO

UPDATE SET
	published = :published,
	locked = :locked,
	hidden = :hidden,
	editor = :editor,
	content = :content
WHERE
	cms_nodes.uid = :uid

RETURNING node;
