-- Insert fulltext document into FTS5 index
-- For contentless FTS5, we specify the rowid explicitly
-- Call deleteFts.sql first if updating an existing entry
INSERT INTO cms_fulltext_fts (rowid, document) VALUES (:rowid, :document);
