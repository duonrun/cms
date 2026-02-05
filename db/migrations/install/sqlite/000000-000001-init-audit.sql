CREATE TABLE audit_nodes (
	node INTEGER NOT NULL,
	parent INTEGER,
	changed TEXT NOT NULL,
	published INTEGER NOT NULL,
	hidden INTEGER NOT NULL,
	locked INTEGER NOT NULL,
	type INTEGER NOT NULL,
	editor INTEGER NOT NULL,
	deleted TEXT,
	content TEXT NOT NULL,
	CONSTRAINT pk_audit_nodes PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_nodes FOREIGN KEY (node)
		REFERENCES cms_nodes (node),
	CONSTRAINT ck_audit_nodes_published CHECK (published IN (0, 1)),
	CONSTRAINT ck_audit_nodes_hidden CHECK (hidden IN (0, 1)),
	CONSTRAINT ck_audit_nodes_locked CHECK (locked IN (0, 1)),
	CONSTRAINT ck_audit_nodes_content_json CHECK (json_valid(content))
);

CREATE TABLE audit_drafts (
	node INTEGER NOT NULL,
	changed TEXT NOT NULL,
	editor INTEGER NOT NULL,
	content TEXT NOT NULL,
	CONSTRAINT pk_audit_drafts PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_drafts FOREIGN KEY (node)
		REFERENCES cms_drafts (node),
	CONSTRAINT ck_audit_drafts_content_json CHECK (json_valid(content))
);

CREATE TABLE audit_users (
	usr INTEGER NOT NULL,
	username TEXT,
	email TEXT,
	pwhash TEXT NOT NULL,
	userrole TEXT NOT NULL,
	active INTEGER NOT NULL,
	data TEXT NOT NULL,
	editor INTEGER NOT NULL,
	changed TEXT NOT NULL,
	deleted TEXT,
	CONSTRAINT pk_audit_users PRIMARY KEY (usr, changed),
	CONSTRAINT fk_audit_users FOREIGN KEY (usr)
		REFERENCES cms_users (usr),
	CONSTRAINT ck_audit_users_active CHECK (active IN (0, 1)),
	CONSTRAINT ck_audit_users_data_json CHECK (json_valid(data))
);

CREATE TRIGGER users_trigger_01_change AFTER UPDATE ON cms_users
FOR EACH ROW BEGIN
	UPDATE cms_users SET changed = CURRENT_TIMESTAMP WHERE usr = NEW.usr;
END;

CREATE TRIGGER users_trigger_02_audit AFTER UPDATE ON cms_users
FOR EACH ROW BEGIN
	INSERT OR IGNORE INTO audit_users (
		usr, username, email, pwhash, userrole, active,
		data, editor, changed, deleted
	) VALUES (
		OLD.usr, OLD.username, OLD.email, OLD.pwhash, OLD.userrole, OLD.active,
		OLD.data, OLD.editor, OLD.changed, OLD.deleted
	);
END;

CREATE TRIGGER authtokens_trigger_01_change AFTER UPDATE ON cms_authtokens
FOR EACH ROW BEGIN
	UPDATE cms_authtokens SET changed = CURRENT_TIMESTAMP WHERE token = NEW.token;
END;

CREATE TRIGGER nodes_trigger_01_delete BEFORE UPDATE ON cms_nodes
FOR EACH ROW
WHEN NEW.deleted IS NOT NULL AND OLD.deleted IS NULL
BEGIN
	SELECT RAISE(ABORT, 'node is still referenced in a menu')
	WHERE EXISTS (
		SELECT 1
		FROM cms_menuitems mi
		WHERE
			json_extract(mi.data, '$.type') = 'node'
			AND json_extract(mi.data, '$.node') = CAST(OLD.node AS TEXT)
	);
END;

CREATE TRIGGER nodes_trigger_02_change AFTER UPDATE ON cms_nodes
FOR EACH ROW BEGIN
	UPDATE cms_nodes SET changed = CURRENT_TIMESTAMP WHERE node = NEW.node;
END;

CREATE TRIGGER nodes_trigger_03_audit AFTER UPDATE ON cms_nodes
FOR EACH ROW BEGIN
	INSERT OR IGNORE INTO audit_nodes (
		node, parent, changed, published, hidden, locked,
		type, editor, deleted, content
	) VALUES (
		OLD.node, OLD.parent, OLD.changed, OLD.published, OLD.hidden, OLD.locked,
		OLD.type, OLD.editor, OLD.deleted, OLD.content
	);
END;

CREATE TRIGGER drafts_trigger_01_audit AFTER UPDATE ON cms_drafts
FOR EACH ROW BEGIN
	INSERT OR IGNORE INTO audit_drafts (
		node, changed, editor, content
	) VALUES (
		OLD.node, OLD.changed, OLD.editor, OLD.content
	);
END;
