-- SQLite DDL for Duon CMS
-- Table names are flattened: cms.table -> cms_table, audit.table -> audit_table
-- JSON columns use TEXT with json_valid() CHECK constraints
-- Timestamps use TEXT in ISO 8601 format (YYYY-MM-DD HH:MM:SS)
-- Auto-increment uses INTEGER PRIMARY KEY (SQLite ROWID alias)


-- User roles table
CREATE TABLE cms_userroles (
	userrole TEXT NOT NULL,
	CONSTRAINT pk_userroles PRIMARY KEY (userrole)
);


-- Users table
CREATE TABLE cms_users (
	usr INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	username TEXT,
	email TEXT,
	pwhash TEXT NOT NULL,
	userrole TEXT NOT NULL,
	active INTEGER NOT NULL CHECK (active IN (0, 1)),
	data TEXT NOT NULL CHECK (json_valid(data)),
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	changed TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	deleted TEXT,
	CONSTRAINT uc_users_uid UNIQUE (uid),
	CONSTRAINT fk_users_userroles FOREIGN KEY (userrole)
		REFERENCES cms_userroles (userrole) ON UPDATE CASCADE,
	CONSTRAINT fk_users_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_users_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_users_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_users_username CHECK
		(username IS NULL OR (length(username) > 0 AND length(username) <= 64)),
	CONSTRAINT ck_users_email CHECK
		(email IS NULL OR (email LIKE '%@%' AND length(email) >= 5 AND length(email) <= 256)),
	CONSTRAINT ck_users_username_or_email CHECK (username IS NOT NULL OR email IS NOT NULL)
);
CREATE UNIQUE INDEX ux_users_username ON cms_users (lower(username))
	WHERE deleted IS NULL AND username IS NOT NULL;
CREATE UNIQUE INDEX ux_users_email ON cms_users (lower(email))
	WHERE deleted IS NULL AND email IS NOT NULL;


-- Auth tokens table
CREATE TABLE cms_authtokens (
	token TEXT NOT NULL,
	usr INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	changed TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
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


-- One-time tokens table
CREATE TABLE cms_onetimetokens (
	token TEXT NOT NULL,
	usr INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	CONSTRAINT pk_onetimetokens PRIMARY KEY (token),
	CONSTRAINT fk_onetimetokens_users FOREIGN KEY (usr)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_onetimetokens_token CHECK (length(token) <= 512)
);


-- Login sessions table
CREATE TABLE cms_loginsessions (
	hash TEXT NOT NULL,
	usr INTEGER NOT NULL,
	expires TEXT NOT NULL,
	CONSTRAINT pk_loginsessions PRIMARY KEY (hash),
	CONSTRAINT uc_loginsessions_usr UNIQUE (usr),
	CONSTRAINT fk_loginsessions_users FOREIGN KEY (usr) REFERENCES cms_users(usr),
	CONSTRAINT ck_loginsessions_hash CHECK (length(hash) <= 254)
);


-- Content types table (page, block, document)
CREATE TABLE cms_types (
	type INTEGER PRIMARY KEY,
	handle TEXT NOT NULL,
	kind TEXT NOT NULL CHECK (kind IN ('page', 'block', 'document')),
	CONSTRAINT uc_types_handle UNIQUE (handle),
	CONSTRAINT ck_types_handle CHECK (length(handle) <= 256)
);


-- Nodes (content items) table
CREATE TABLE cms_nodes (
	node INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	parent INTEGER,
	published INTEGER NOT NULL DEFAULT 0 CHECK (published IN (0, 1)),
	hidden INTEGER NOT NULL DEFAULT 0 CHECK (hidden IN (0, 1)),
	locked INTEGER NOT NULL DEFAULT 0 CHECK (locked IN (0, 1)),
	type INTEGER NOT NULL,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	changed TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	deleted TEXT,
	content TEXT NOT NULL CHECK (json_valid(content)),
	CONSTRAINT uc_nodes_uid UNIQUE (uid),
	CONSTRAINT fk_nodes_users_creator FOREIGN KEY (creator)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_nodes_nodes FOREIGN KEY (parent)
		REFERENCES cms_nodes (node),
	CONSTRAINT fk_nodes_users_editor FOREIGN KEY (editor)
		REFERENCES cms_users (usr),
	CONSTRAINT fk_nodes_types FOREIGN KEY (type)
		REFERENCES cms_types (type) ON UPDATE CASCADE ON DELETE NO ACTION,
	CONSTRAINT ck_nodes_uid CHECK (length(uid) <= 64)
);
-- Note: SQLite cannot create GIN indexes like PostgreSQL
-- For JSON queries, consider generated columns or application-level optimization
CREATE INDEX ix_nodes_type ON cms_nodes (type);
CREATE INDEX ix_nodes_parent ON cms_nodes (parent) WHERE parent IS NOT NULL;
CREATE INDEX ix_nodes_deleted ON cms_nodes (deleted) WHERE deleted IS NULL;


-- Fulltext search table (FTS5 virtual table created separately in Step 7)
-- For now, create a regular table that can be used until FTS5 is set up
CREATE TABLE cms_fulltext (
	node INTEGER NOT NULL,
	locale TEXT NOT NULL,
	document TEXT NOT NULL,
	CONSTRAINT pk_fulltext PRIMARY KEY (node, locale),
	CONSTRAINT fk_fulltext_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node),
	CONSTRAINT ck_fulltext_locale CHECK (length(locale) <= 32)
);


-- URL paths table
CREATE TABLE cms_urlpaths (
	node INTEGER NOT NULL,
	path TEXT NOT NULL,
	locale TEXT NOT NULL,
	creator INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	created TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
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
	WHERE inactive IS NULL;


-- Drafts table
CREATE TABLE cms_drafts (
	node INTEGER NOT NULL,
	changed TEXT NOT NULL,
	editor INTEGER NOT NULL,
	content TEXT NOT NULL CHECK (json_valid(content)),
	CONSTRAINT pk_drafts PRIMARY KEY (node),
	CONSTRAINT fk_drafts_nodes FOREIGN KEY (node) REFERENCES cms_nodes (node)
);


-- Menus table
CREATE TABLE cms_menus (
	menu TEXT NOT NULL,
	description TEXT NOT NULL,
	CONSTRAINT pk_menus PRIMARY KEY (menu),
	CONSTRAINT ck_menus_menu CHECK (length(menu) <= 32),
	CONSTRAINT ck_menus_description CHECK (length(description) <= 128)
);


-- Menu items table
CREATE TABLE cms_menuitems (
	item TEXT NOT NULL,
	parent TEXT,
	menu TEXT NOT NULL,
	displayorder INTEGER NOT NULL,
	data TEXT NOT NULL CHECK (json_valid(data)),
	CONSTRAINT pk_menuitems PRIMARY KEY (item),
	CONSTRAINT fk_menuitems_menus FOREIGN KEY (menu)
		REFERENCES cms_menus (menu) ON UPDATE CASCADE,
	CONSTRAINT fk_menuitems_menuitems FOREIGN KEY (parent)
		REFERENCES cms_menuitems (item),
	CONSTRAINT ck_menuitems_item CHECK (length(item) <= 64),
	CONSTRAINT ck_menuitems_parent CHECK (length(parent) <= 64)
);


-- Topics table (for tag grouping)
CREATE TABLE cms_topics (
	topic INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	name TEXT NOT NULL CHECK (json_valid(name)),
	color TEXT NOT NULL,
	CONSTRAINT uc_topics_uid UNIQUE (uid),
	CONSTRAINT ck_topics_uid CHECK (length(uid) <= 64),
	CONSTRAINT ck_topics_color CHECK (length(color) <= 128)
);


-- Tags table
CREATE TABLE cms_tags (
	tag INTEGER PRIMARY KEY,
	uid TEXT NOT NULL,
	name TEXT NOT NULL CHECK (json_valid(name)),
	topic INTEGER NOT NULL,
	CONSTRAINT uc_tags_uid UNIQUE (uid),
	CONSTRAINT fk_tags_topics FOREIGN KEY (topic)
		REFERENCES cms_topics (topic),
	CONSTRAINT ck_tags_uid CHECK (length(uid) <= 64)
);


-- Node-Tag relationship table
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


-- Audit tables for version history

-- Nodes audit table
CREATE TABLE audit_nodes (
	node INTEGER NOT NULL,
	parent INTEGER,
	changed TEXT NOT NULL,
	published INTEGER NOT NULL CHECK (published IN (0, 1)),
	hidden INTEGER NOT NULL CHECK (hidden IN (0, 1)),
	locked INTEGER NOT NULL CHECK (locked IN (0, 1)),
	type TEXT NOT NULL,
	editor INTEGER NOT NULL,
	deleted TEXT,
	content TEXT NOT NULL CHECK (json_valid(content)),
	CONSTRAINT pk_audit_nodes PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node)
);


-- Drafts audit table
CREATE TABLE audit_drafts (
	node INTEGER NOT NULL,
	changed TEXT NOT NULL,
	editor INTEGER NOT NULL,
	content TEXT NOT NULL CHECK (json_valid(content)),
	CONSTRAINT pk_audit_drafts PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_drafts FOREIGN KEY (node)
		REFERENCES cms_drafts (node)
);


-- Users audit table
CREATE TABLE audit_users (
	usr INTEGER NOT NULL,
	username TEXT,
	email TEXT,
	pwhash TEXT NOT NULL,
	userrole TEXT NOT NULL,
	active INTEGER NOT NULL CHECK (active IN (0, 1)),
	data TEXT NOT NULL CHECK (json_valid(data)),
	editor INTEGER NOT NULL,
	changed TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now')),
	deleted TEXT,
	CONSTRAINT pk_audit_users PRIMARY KEY (usr, changed),
	CONSTRAINT fk_audit_users FOREIGN KEY (usr)
		REFERENCES cms_users (usr)
);


-- Triggers for automatic 'changed' timestamp updates

CREATE TRIGGER users_trigger_01_change
	BEFORE UPDATE ON cms_users
	FOR EACH ROW
BEGIN
	UPDATE cms_users SET changed = strftime('%Y-%m-%d %H:%M:%f', 'now')
	WHERE usr = NEW.usr;
END;

CREATE TRIGGER authtokens_trigger_01_change
	BEFORE UPDATE ON cms_authtokens
	FOR EACH ROW
BEGIN
	UPDATE cms_authtokens SET changed = strftime('%Y-%m-%d %H:%M:%f', 'now')
	WHERE token = NEW.token;
END;

-- Note: nodes_trigger_02_change removed - changed timestamp is set explicitly
-- in save.sql and delete.sql queries to avoid trigger-based UPDATE conflicts


-- Triggers for audit logging

CREATE TRIGGER users_trigger_02_audit
	AFTER UPDATE ON cms_users
	FOR EACH ROW
BEGIN
	INSERT OR IGNORE INTO audit_users (
		usr, username, email, pwhash, userrole, active,
		data, editor, changed, deleted
	) VALUES (
		OLD.usr, OLD.username, OLD.email, OLD.pwhash, OLD.userrole, OLD.active,
		OLD.data, OLD.editor, OLD.changed, OLD.deleted
	);
END;

CREATE TRIGGER nodes_trigger_03_audit
	AFTER UPDATE ON cms_nodes
	FOR EACH ROW
BEGIN
	INSERT OR IGNORE INTO audit_nodes (
		node, parent, changed, published, hidden, locked,
		type, editor, deleted, content
	) VALUES (
		OLD.node, OLD.parent, OLD.changed, OLD.published, OLD.hidden, OLD.locked,
		OLD.type, OLD.editor, OLD.deleted, OLD.content
	);
END;

CREATE TRIGGER drafts_trigger_01_audit
	AFTER UPDATE ON cms_drafts
	FOR EACH ROW
BEGIN
	INSERT OR IGNORE INTO audit_drafts (
		node, changed, editor, content
	) VALUES (
		OLD.node, OLD.changed, OLD.editor, OLD.content
	);
END;


-- Trigger to prevent deleting nodes referenced in menus
CREATE TRIGGER nodes_trigger_01_delete
	BEFORE UPDATE ON cms_nodes
	FOR EACH ROW
	WHEN NEW.deleted IS NOT NULL AND OLD.deleted IS NULL
BEGIN
	SELECT RAISE(ABORT, 'node is still referenced in a menu')
	WHERE EXISTS (
		SELECT 1 FROM cms_menuitems mi
		WHERE json_extract(mi.data, '$.type') = 'node'
		AND json_extract(mi.data, '$.node') = CAST(OLD.node AS TEXT)
	);
END;
