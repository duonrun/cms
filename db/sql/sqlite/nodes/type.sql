SELECT
	type,
	handle,
	kind
FROM
	cms_types
WHERE
	handle = :handle;
