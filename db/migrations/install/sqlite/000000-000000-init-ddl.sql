CREATE TABLE cms_userroles (
	userrole TEXT NOT NULL,
	CONSTRAINT pk_userroles PRIMARY KEY (userrole)
);

CREATE TABLE cms_users (
	usr INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	username TEXT,
	email TEXT,
	pwhash TEXT NOT NULL,
	userrole TEXT NOT NULL,
	active INTEGER NOT NULL,
	data TEXT NOT NULL,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	deleted TEXT,
	CONSTRAINT uc_users_uid UNIQUE (uid),
	CONSTRAINT fk_users_userroles FOREIGN KEY (userrole)
		REFERENCES cms_userroles (userrole),
	CONSTRAINT fk_users_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_users_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_users_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_users_username CHECK
		(username IS NULL OR (length(username) > 0 AND length(username) <= 64)),
	CONSTRAINT ck_users_email CHECK
		(email IS NULL OR (instr(email, '@') > 0 AND length(email) >= 5 AND length(email) <= 256)),
	CONSTRAINT ck_users_username_or_email CHECK (username IS NOT NULL OR email IS NOT NULL),
	CONSTRAINT ck_users_active CHECK (active IN (0, 1)),
	CONSTRAINT ck_users_data_json CHECK (json_valid(data))
);
CREATE UNIQUE INDEX ux_users_username ON cms_users
	(lower(username)) WHERE (deleted IS NULL AND username IS NOT NULL);
CREATE UNIQUE INDEX ux_users_email ON cms_users
	(lower(email)) WHERE (deleted IS NULL AND email IS NOT NULL);
CREATE INDEX ix_users_login_username ON cms_users (username)
	WHERE (deleted IS NULL AND active = 1 AND userrole != 'system' AND username IS NOT NULL);
CREATE INDEX ix_users_login_email ON cms_users (email)
	WHERE (deleted IS NULL AND active = 1 AND userrole != 'system' AND email IS NOT NULL);

CREATE TABLE cms_authtokens (
	token TEXT NOT NULL,
	usr INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	CONSTRAINT pk_authtokens PRIMARY KEY (token),
	CONSTRAINT fk_authtokens_users FOREIGN KEY (usr)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_authtokens_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_authtokens_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT uc_authtokens_usr UNIQUE (usr),
	CONSTRAINT ck_authtokens_token CHECK (length(token) <= 512)
);

CREATE TABLE cms_onetimetokens (
	token TEXT NOT NULL,
	usr INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT pk_onetimetokens PRIMARY KEY (token),
	CONSTRAINT fk_onetimetokens_users FOREIGN KEY (usr)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_onetimetokens_token CHECK (length(token) <= 512)
);
CREATE INDEX ix_onetimetokens_created ON cms_onetimetokens (created);

CREATE TABLE cms_loginsessions (
	hash TEXT NOT NULL,
	usr INTEGER NOT NULL,
	expires TEXT NOT NULL,
	CONSTRAINT pk_loginsessions PRIMARY KEY (hash),
	CONSTRAINT uc_loginsessions_usr UNIQUE (usr),
	CONSTRAINT fk_loginsessions_users FOREIGN KEY (usr) REFERENCES cms_users (usr),
	CONSTRAINT ck_loginsessions_hash CHECK (length(hash) <= 254)
);

CREATE TABLE cms_types (
	type INTEGER PRIMARY KEY,
	handle TEXT NOT NULL,
	kind TEXT NOT NULL,
	CONSTRAINT uc_types_handle UNIQUE (handle),
	CONSTRAINT ck_types_handle CHECK (length(handle) <= 256),
	CONSTRAINT ck_types_kind CHECK (kind IN ('page', 'block', 'document'))
);

CREATE TABLE cms_nodes (
	node INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	parent INTEGER,
	published INTEGER NOT NULL DEFAULT 0,
	hidden INTEGER NOT NULL DEFAULT 0,
	locked INTEGER NOT NULL DEFAULT 0,
	type INTEGER NOT NULL,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	changed TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	deleted TEXT,
	content TEXT NOT NULL,
	CONSTRAINT uc_nodes_uid UNIQUE (uid),
	CONSTRAINT fk_nodes_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_nodes_nodes FOREIGN KEY (parent)
		REFERENCES cms_nodes (node),
	CONSTRAINT fk_nodes_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_nodes_types FOREIGN KEY (type)
		REFERENCES cms_types (type),
	CONSTRAINT ck_nodes_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_nodes_published CHECK (published IN (0, 1)),
	CONSTRAINT ck_nodes_hidden CHECK (hidden IN (0, 1)),
	CONSTRAINT ck_nodes_locked CHECK (locked IN (0, 1)),
	CONSTRAINT ck_nodes_content_json CHECK (json_valid(content))
);
CREATE INDEX ix_nodes_type ON cms_nodes (type);
CREATE INDEX ix_nodes_visibility ON cms_nodes (published, hidden, type)
	WHERE (deleted IS NULL);

CREATE VIRTUAL TABLE cms_fulltext USING fts5 (
	node UNINDEXED,
	locale UNINDEXED,
	document,
	tokenize='unicode61'
);

CREATE TABLE cms_urlpaths (
	node INTEGER NOT NULL,
	path TEXT NOT NULL,
	locale TEXT NOT NULL,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	inactive TEXT,
	CONSTRAINT pk_urlpaths PRIMARY KEY (node, locale, path),
	CONSTRAINT fk_urlpaths_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node),
	CONSTRAINT fk_urlpaths_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_urlpaths_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_urlpaths_path CHECK (length(path) <= 512),
	CONSTRAINT ck_urlpaths_locale CHECK (length(locale) <= 32)
);
CREATE UNIQUE INDEX ux_urlpaths_path ON cms_urlpaths (path);
CREATE UNIQUE INDEX ux_urlpaths_locale ON cms_urlpaths (node, locale)
	WHERE (inactive IS NULL);

CREATE TABLE cms_drafts (
	node INTEGER NOT NULL,
	changed TEXT NOT NULL,
	editor INTEGER NOT NULL,
	content TEXT NOT NULL,
	CONSTRAINT pk_drafts PRIMARY KEY (node),
	CONSTRAINT fk_drafts_nodes FOREIGN KEY (node) REFERENCES cms_nodes (node),
	CONSTRAINT ck_drafts_content_json CHECK (json_valid(content))
);

CREATE TABLE cms_menus (
	menu TEXT NOT NULL,
	description TEXT NOT NULL,
	CONSTRAINT pk_menus PRIMARY KEY (menu),
	CONSTRAINT ck_menus_menu CHECK (length(menu) <= 32),
	CONSTRAINT ck_menus_description CHECK (length(description) <= 128)
);

CREATE TABLE cms_menuitems (
	item TEXT NOT NULL,
	parent TEXT,
	menu TEXT NOT NULL,
	displayorder INTEGER NOT NULL,
	data TEXT NOT NULL,
	CONSTRAINT pk_menuitems PRIMARY KEY (item),
	CONSTRAINT fk_menuitems_menus FOREIGN KEY (menu)
		REFERENCES cms_menus (menu),
	CONSTRAINT fk_menuitems_menuitems FOREIGN KEY (parent)
		REFERENCES cms_menuitems (item),
	CONSTRAINT ck_menuitems_item CHECK (length(item) <= 64),
	CONSTRAINT ck_menuitems_parent CHECK (parent IS NULL OR length(parent) <= 64),
	CONSTRAINT ck_menuitems_data_json CHECK (json_valid(data))
);

CREATE TABLE cms_topics (
	topic INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	name TEXT NOT NULL,
	color TEXT NOT NULL,
	CONSTRAINT uc_topics_uid UNIQUE (uid),
	CONSTRAINT ck_topics_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_topics_color CHECK (length(color) <= 128),
	CONSTRAINT ck_topics_name_json CHECK (json_valid(name))
);

CREATE TABLE cms_tags (
	tag INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	name TEXT NOT NULL,
	topic INTEGER NOT NULL,
	CONSTRAINT uc_tags_uid UNIQUE (uid),
	CONSTRAINT fk_tags_topics FOREIGN KEY (topic)
		REFERENCES cms_topics (topic),
	CONSTRAINT ck_tags_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_tags_name_json CHECK (json_valid(name))
);

CREATE TABLE cms_nodetags (
	node INTEGER NOT NULL,
	tag INTEGER NOT NULL,
	sort INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT pk_nodetags PRIMARY KEY (node, tag),
	CONSTRAINT fk_nodetags_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node),
	CONSTRAINT fk_nodetags_tags FOREIGN KEY (tag)
		REFERENCES cms_tags (tag)
);
