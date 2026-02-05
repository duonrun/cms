UPDATE cms.users
SET
	email = :email,
	username = :username,
	data = :data,
	editor = :editor,
	pwhash = :pwhash
WHERE
	usr = :usr;
