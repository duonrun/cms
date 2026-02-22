INSERT INTO cms.userroles (userrole) VALUES ('system'), ('superuser'), ('admin'), ('editor');

INSERT INTO cms.users (
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
	true,
	'{}'::jsonb,
	1,
	1
);

INSERT INTO public.migrations (migration, applied) VALUES
	('000000-000002-named-checks.sql', now()),
	('000000-000003-fix-authtokens-trigger.sql', now()),
	('000000-000004-drop-node-kind.sql', now()),
	('000000-000005-rename-html-to-richtext.sql', now());
