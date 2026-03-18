INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
VALUES (
	'test-homepage',
	NULL,
	1,
	0,
	0,
	(SELECT type FROM cms.types WHERE handle = 'test-home' LIMIT 1),
	1,
	1,
	'{"title":{"type":"text","value":{"de":"Testhomepage","en":"Test Homepage"}},"content":{"type":"richtext","value":{"de":"<p>Willkommen auf der Testseite</p>","en":"<p>Welcome to the test page</p>"}}}'
)
ON CONFLICT (uid) DO NOTHING;

INSERT INTO cms.urlpaths (node, path, locale, creator, editor)
SELECT node, '/', 'en', 1, 1
FROM cms.nodes
WHERE uid = 'test-homepage'
ON CONFLICT DO NOTHING;

INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
VALUES (
	'test-article-1',
	NULL,
	1,
	0,
	0,
	(SELECT type FROM cms.types WHERE handle = 'test-article' LIMIT 1),
	1,
	1,
	'{"title":{"type":"text","value":{"de":"Testartikel 1","en":"Test Article 1"}},"body":{"type":"richtext","value":{"de":"<p>Inhalt des ersten Testartikels</p>","en":"<p>Content of the first test article</p>"}}}'
)
ON CONFLICT (uid) DO NOTHING;

INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
VALUES (
	'test-article-2',
	NULL,
	0,
	0,
	0,
	(SELECT type FROM cms.types WHERE handle = 'test-article' LIMIT 1),
	1,
	1,
	'{"title":{"type":"text","value":{"de":"Testartikel 2 (Entwurf)","en":"Test Article 2 (Draft)"}},"body":{"type":"richtext","value":{"de":"<p>Dies ist ein unveroffentlichter Artikel</p>","en":"<p>This is an unpublished article</p>"}}}'
)
ON CONFLICT (uid) DO NOTHING;

INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
VALUES (
	'test-block-1',
	NULL,
	1,
	0,
	0,
	(SELECT type FROM cms.types WHERE handle = 'test-block' LIMIT 1),
	1,
	1,
	'{"content":{"type":"richtext","value":{"de":"<div class=\"test-block\">Testblock Inhalt</div>","en":"<div class=\"test-block\">Test block content</div>"}}}'
)
ON CONFLICT (uid) DO NOTHING;
