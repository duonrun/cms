-- Clean orphaned legacy fulltext entries
-- This cleans the old cms_fulltext table for backwards compatibility
DELETE FROM cms_fulltext
WHERE node NOT IN (
	SELECT n.node
	FROM cms_nodes n
	WHERE n.deleted IS NULL
	  AND n.published = 1
);
