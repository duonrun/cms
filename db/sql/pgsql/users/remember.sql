INSERT INTO cms.loginsessions
	(hash, usr, expires)
VALUES
	(:hash, :user, (:expires)::timestamptz)

ON CONFLICT (usr) DO

UPDATE SET
	expires = (:expires)::timestamptz,
	hash = :hash;