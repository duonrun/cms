-- Delete existing FTS5 entry for a given rowid
-- Used before upserting to handle updates in contentless FTS5 tables
DELETE FROM cms_fulltext_fts WHERE rowid = :rowid;
