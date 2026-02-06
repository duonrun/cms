CREATE VIRTUAL TABLE IF NOT EXISTS cms_fulltext USING fts5 (
	node UNINDEXED,
	locale UNINDEXED,
	document,
	tokenize='unicode61'
);
