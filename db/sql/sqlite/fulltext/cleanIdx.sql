-- Clean orphaned fulltext index mapping entries
DELETE FROM cms_fulltext_idx
WHERE node NOT IN (
	SELECT n.node
	FROM cms_nodes n
	WHERE n.deleted IS NULL
	  AND n.published = 1
);
