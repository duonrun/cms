INSERT INTO cms.nodes (
	uid,
	parent,
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
	:parent,
	type,
	:published,
	:locked,
	:hidden,
	:editor,
	:editor,
	:content
FROM
	cms.types t
WHERE
	t.handle = :type

ON CONFLICT (uid) DO

UPDATE SET
	parent = :parent,
	published = :published,
	locked = :locked,
	hidden = :hidden,
	editor = :editor,
	content = :content
WHERE
	cms.nodes.uid = :uid

RETURNING node;
