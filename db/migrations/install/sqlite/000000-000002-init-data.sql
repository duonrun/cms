-- SQLite seed data: user roles and system user

INSERT INTO cms_userroles (userrole) VALUES ('system'), ('superuser'), ('admin'), ('editor');

INSERT INTO cms_users (
	uid,
	username,
	email,
	pwhash,
	userrole,
	active,
	data,
	creator,
	editor
) VALUES (
	'0000000000000',
	'system',
	'system@duon.dev',
	'$2y$13$r30g3d99Nf5r4t6L1eDAa.FcMNazGHpwndT0Ak6Bvfhr7SEhaeepC',
	'system',
	1,
	'{}',
	1,
	1
);
