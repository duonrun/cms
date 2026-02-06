SELECT
	type,
	handle,
	kind
FROM
	cms.types
WHERE
	handle = :handle;
