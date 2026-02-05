-- Sample nodes for integration testing (SQLite)
-- Assumes basic-types.sql has been loaded

-- Test homepage
INSERT OR IGNORE INTO cms_nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
SELECT
    'test-homepage',
    NULL,
    1,
    0,
    0,
    type,
    1,
    1,
    '{"title":{"type":"text","value":{"de":"Testhomepage","en":"Test Homepage"}},"content":{"type":"html","value":{"de":"<p>Willkommen auf der Testseite</p>","en":"<p>Welcome to the test page</p>"}}}'
FROM cms_types
WHERE handle = 'test-home'
LIMIT 1;

-- Add URL path for homepage
INSERT OR IGNORE INTO cms_urlpaths (node, path, locale, creator, editor)
SELECT
    node,
    '/',
    'en',
    1,
    1
FROM cms_nodes
WHERE uid = 'test-homepage'
LIMIT 1;

-- Test article 1 (published)
INSERT OR IGNORE INTO cms_nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
SELECT
    'test-article-1',
    NULL,
    1,
    0,
    0,
    type,
    1,
    1,
    '{"title":{"type":"text","value":{"de":"Testartikel 1","en":"Test Article 1"}},"body":{"type":"html","value":{"de":"<p>Inhalt des ersten Testartikels</p>","en":"<p>Content of the first test article</p>"}}}'
FROM cms_types
WHERE handle = 'test-article'
LIMIT 1;

-- Test article 2 (unpublished/draft)
INSERT OR IGNORE INTO cms_nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
SELECT
    'test-article-2',
    NULL,
    0,
    0,
    0,
    type,
    1,
    1,
    '{"title":{"type":"text","value":{"de":"Testartikel 2 (Entwurf)","en":"Test Article 2 (Draft)"}},"body":{"type":"html","value":{"de":"<p>Dies ist ein unveröffentlichter Artikel</p>","en":"<p>This is an unpublished article</p>"}}}'
FROM cms_types
WHERE handle = 'test-article'
LIMIT 1;

-- Test block
INSERT OR IGNORE INTO cms_nodes (uid, parent, published, hidden, locked, type, creator, editor, content)
SELECT
    'test-block-1',
    NULL,
    1,
    0,
    0,
    type,
    1,
    1,
    '{"content":{"type":"html","value":{"de":"<div class=\"test-block\">Testblock Inhalt</div>","en":"<div class=\"test-block\">Test block content</div>"}}}'
FROM cms_types
WHERE handle = 'test-block'
LIMIT 1;
