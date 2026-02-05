-- Basic content types for integration testing (SQLite version)
-- These types can be used across multiple test cases

-- Test page types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-page', 'page');
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-home', 'page');
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-article', 'page');

-- Test document types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-document', 'document');
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-media', 'document');

-- Test block types
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-block', 'block');
INSERT OR IGNORE INTO cms_types (handle, kind) VALUES ('test-widget', 'block');
