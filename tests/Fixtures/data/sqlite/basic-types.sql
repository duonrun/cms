-- Basic content types for integration testing (SQLite)
-- These types can be used across multiple test cases

-- Test page types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES
    ('test-page', 'page'),
    ('test-home', 'page'),
    ('test-article', 'page');

-- Test document types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES
    ('test-document', 'document'),
    ('test-media', 'document');

-- Test block types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES
    ('test-block', 'block'),
    ('test-widget', 'block');
