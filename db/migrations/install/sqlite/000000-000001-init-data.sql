-- SQLite seed data for Duon CMS

INSERT INTO cms_userroles (userrole) VALUES ('system'), ('superuser'), ('admin'), ('editor');

INSERT INTO cms_users (
	usr,
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
	1,
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

-- Mark update migrations as already applied (they are PostgreSQL-specific fixes)
INSERT INTO migrations (migration, applied) VALUES
	('000000-000002-named-checks.sql', strftime('%Y-%m-%d %H:%M:%S', 'now')),
	('000000-000003-fix-authtokens-trigger.sql', strftime('%Y-%m-%d %H:%M:%S', 'now'));
