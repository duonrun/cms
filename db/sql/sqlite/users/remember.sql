INSERT INTO cms_loginsessions
	(hash, usr, expires)
VALUES
	(:hash, :user, :expires)

ON CONFLICT (usr) DO

UPDATE SET
	expires = :expires,
	hash = :hash;
