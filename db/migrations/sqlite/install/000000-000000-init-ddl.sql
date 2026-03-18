CREATE TABLE cms.userroles (
	userrole text NOT NULL,
	PRIMARY KEY (userrole)
);

CREATE TABLE cms.users (
	usr integer PRIMARY KEY AUTOINCREMENT,
	uid text NOT NULL UNIQUE,
	username text,
	email text,
	pwhash text NOT NULL,
	userrole text NOT NULL,
	active integer NOT NULL,
	data text NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	deleted text,
	FOREIGN KEY (userrole) REFERENCES userroles (userrole),
	FOREIGN KEY (creator) REFERENCES users (usr),
	FOREIGN KEY (editor) REFERENCES users (usr)
);

CREATE UNIQUE INDEX cms.ux_users_username ON users (lower(username))
	WHERE deleted IS NULL AND username IS NOT NULL;
CREATE UNIQUE INDEX cms.ux_users_email ON users (lower(email))
	WHERE deleted IS NULL AND email IS NOT NULL;

CREATE TABLE cms.authtokens (
	token text NOT NULL,
	usr integer NOT NULL UNIQUE,
	created text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	creator integer NOT NULL,
	editor integer NOT NULL,
	PRIMARY KEY (token),
	FOREIGN KEY (usr) REFERENCES users (usr),
	FOREIGN KEY (creator) REFERENCES users (usr),
	FOREIGN KEY (editor) REFERENCES users (usr)
);

CREATE TABLE cms.onetimetokens (
	token text NOT NULL,
	usr integer NOT NULL,
	created text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (token),
	FOREIGN KEY (usr) REFERENCES users (usr)
);

CREATE TABLE cms.loginsessions (
	hash text NOT NULL,
	usr integer NOT NULL UNIQUE,
	expires text NOT NULL,
	PRIMARY KEY (hash),
	FOREIGN KEY (usr) REFERENCES users (usr)
);

CREATE TABLE cms.types (
	type integer PRIMARY KEY AUTOINCREMENT,
	handle text NOT NULL UNIQUE
);

CREATE TABLE cms.nodes (
	node integer PRIMARY KEY AUTOINCREMENT,
	uid text NOT NULL UNIQUE,
	parent integer,
	published integer NOT NULL DEFAULT 0,
	hidden integer NOT NULL DEFAULT 0,
	locked integer NOT NULL DEFAULT 0,
	type integer NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	deleted text,
	content text NOT NULL,
	FOREIGN KEY (parent) REFERENCES nodes (node),
	FOREIGN KEY (type) REFERENCES types (type),
	FOREIGN KEY (creator) REFERENCES users (usr),
	FOREIGN KEY (editor) REFERENCES users (usr)
);

CREATE TABLE cms.fulltext (
	node integer NOT NULL,
	locale text NOT NULL,
	document text NOT NULL,
	PRIMARY KEY (node, locale),
	FOREIGN KEY (node) REFERENCES nodes (node)
);

CREATE TABLE cms.urlpaths (
	node integer NOT NULL,
	path text NOT NULL,
	locale text NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	inactive text,
	PRIMARY KEY (node, locale, path),
	FOREIGN KEY (node) REFERENCES nodes (node),
	FOREIGN KEY (creator) REFERENCES users (usr),
	FOREIGN KEY (editor) REFERENCES users (usr)
);

CREATE UNIQUE INDEX cms.ux_urlpaths_path ON urlpaths (path);
CREATE UNIQUE INDEX cms.ux_urlpaths_locale ON urlpaths (node, locale)
	WHERE inactive IS NULL;

CREATE TABLE cms.drafts (
	node integer NOT NULL,
	changed text NOT NULL,
	editor integer NOT NULL,
	content text NOT NULL,
	PRIMARY KEY (node),
	FOREIGN KEY (node) REFERENCES nodes (node)
);

CREATE TABLE cms.nodetags (
	node integer NOT NULL,
	tag text NOT NULL,
	locale text,
	PRIMARY KEY (node, tag, locale),
	FOREIGN KEY (node) REFERENCES nodes (node)
);

CREATE TABLE cms.menus (
	menu text NOT NULL,
	description text,
	PRIMARY KEY (menu)
);

CREATE TABLE cms.menuitems (
	item text NOT NULL,
	parent text,
	menu text NOT NULL,
	displayorder integer NOT NULL,
	data text NOT NULL,
	PRIMARY KEY (item, menu),
	FOREIGN KEY (menu) REFERENCES menus (menu) ON DELETE CASCADE,
	FOREIGN KEY (parent, menu) REFERENCES menuitems (item, menu) ON DELETE CASCADE
);

CREATE TABLE migrations (
	migration text NOT NULL,
	applied text NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (migration)
);
