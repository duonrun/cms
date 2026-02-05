-- Performance indexes for common query patterns
-- These indexes improve query performance for tag lookups

-- Index on cms.nodetags.tag for reverse lookups (find nodes by tag)
-- The primary key is (node, tag), so lookups by tag alone require a scan
CREATE INDEX IF NOT EXISTS ix_nodetags_tag ON cms.nodetags (tag);
