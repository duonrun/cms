DELETE FROM cms_fulltext
WHERE
	node = :node
	AND locale = :locale;

INSERT INTO cms_fulltext (node, locale, document)
VALUES (:node, :locale, :document);
