-- Get the FTS5 rowid for a node/locale pair
SELECT rowid FROM cms_fulltext_idx WHERE node = :node AND locale = :locale;
