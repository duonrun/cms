-- Upsert fulltext document for a node/locale
-- Uses setweight() to apply weight categories (A, B, C, D)
-- where A is highest priority and D is lowest
-- Note: Parameters must have unique names for PDO
INSERT INTO cms.fulltext (node, locale, document)
VALUES (
	:node,
	:locale,
	setweight(to_tsvector(:config, coalesce(:text_a, '')), 'A') ||
	setweight(to_tsvector(:config, coalesce(:text_b, '')), 'B') ||
	setweight(to_tsvector(:config, coalesce(:text_c, '')), 'C') ||
	setweight(to_tsvector(:config, coalesce(:text_d, '')), 'D')
)
ON CONFLICT (node, locale)
DO UPDATE SET document = EXCLUDED.document;
