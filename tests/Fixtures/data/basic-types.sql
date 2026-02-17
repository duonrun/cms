-- Basic content types for integration testing
-- These types can be used across multiple test cases

INSERT INTO cms.types (handle) VALUES
    ('test-page'),
    ('test-home'),
    ('test-article'),
    ('test-document'),
    ('test-media'),
    ('test-block'),
    ('test-widget')
ON CONFLICT (handle) DO NOTHING;
