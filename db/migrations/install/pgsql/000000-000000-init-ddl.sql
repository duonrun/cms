-- Extensions for CMS functionality
-- btree_gist: Reserved for future exclusion constraints (e.g., date range overlaps)
-- unaccent: For accent-insensitive fulltext search (optional but recommended)
-- Note: tsvector GIN indexes work without extensions
CREATE EXTENSION IF NOT EXISTS btree_gist;
CREATE EXTENSION IF NOT EXISTS unaccent;

CREATE SCHEMA cms;
CREATE SCHEMA audit;

CREATE TYPE cms.contenttype AS ENUM ('page', 'block', 'document');


CREATE FUNCTION cms.update_changed_column()
	RETURNS TRIGGER AS $$
BEGIN
   NEW.changed = now();
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TABLE cms.userroles (
	userrole text NOT NULL,
	CONSTRAINT pk_userroles PRIMARY KEY (userrole)
);


CREATE TABLE cms.users (
	usr integer GENERATED ALWAYS AS IDENTITY,
	uid text NOT NULL,
	username text,
	email text,
	pwhash text NOT NULL,
	userrole text NOT NULL,
	active boolean NOT NULL,
	data jsonb NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created timestamp with time zone NOT NULL DEFAULT now(),
	changed timestamp with time zone NOT NULL DEFAULT now(),
	deleted timestamp with time zone,
	CONSTRAINT pk_users PRIMARY KEY (usr),
	CONSTRAINT uc_users_uid UNIQUE (uid),
	CONSTRAINT fk_users_userroles FOREIGN KEY (userrole)
		REFERENCES cms.userroles (userrole) ON UPDATE CASCADE,
	CONSTRAINT fk_users_users_creator FOREIGN KEY (creator)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_users_users_editor FOREIGN KEY (editor)
		REFERENCES cms.users (usr),
	CONSTRAINT ck_users_uid CHECK (char_length(uid) <= 64),
	CONSTRAINT ck_users_username CHECK
		(username IS NULL OR (char_length(username) > 0 AND char_length(username) <= 64)),
	CONSTRAINT ck_users_email CHECK
		(email IS NULL OR (email SIMILAR TO '%@%' AND char_length(email) >= 5 AND char_length(email) <= 256)),
	CONSTRAINT ck_users_username_or_email CHECK (username IS NOT NULL OR email IS NOT NULL)
);
CREATE UNIQUE INDEX ux_users_username ON cms.users
	USING btree (lower(username)) WHERE (deleted IS NULL AND username IS NOT NULL);
CREATE UNIQUE INDEX ux_users_email ON cms.users
	USING btree (lower(email)) WHERE (deleted IS NULL AND email IS NOT NULL);
CREATE FUNCTION cms.process_users_audit()
	RETURNS TRIGGER AS $$
BEGIN
	INSERT INTO audit.users (
		usr, username, email, pwhash, userrole, active,
		data, editor, changed, deleted
	) VALUES (
		OLD.usr, OLD.username, OLD.email, OLD.pwhash, OLD.userrole, OLD.active,
		OLD.data, OLD.editor, OLD.changed, OLD.deleted
	);

	RETURN OLD;
EXCEPTION WHEN unique_violation THEN
	RAISE WARNING 'Duplicate users audit row skipped. user: %, changed: %', OLD.usr, OLD.changed;
	RETURN NULL;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER users_trigger_01_change BEFORE UPDATE ON cms.users
	FOR EACH ROW EXECUTE FUNCTION cms.update_changed_column();
CREATE TRIGGER users_trigger_02_audit AFTER UPDATE
	ON cms.users FOR EACH ROW EXECUTE PROCEDURE
	cms.process_users_audit();


CREATE TABLE cms.authtokens (
	token text NOT NULL,
	usr integer NOT NULL,
	created timestamp with time zone NOT NULL DEFAULT now(),
	changed timestamp with time zone NOT NULL DEFAULT now(),
	creator integer NOT NULL,
	editor integer NOT NULL,
	CONSTRAINT pk_authtokens PRIMARY KEY (token),
	CONSTRAINT fk_authtokens_users FOREIGN KEY (usr)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_authtokens_users_creator FOREIGN KEY (creator)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_authtokens_users_editor FOREIGN KEY (editor)
		REFERENCES cms.users (usr),
	CONSTRAINT uc_authtokens_usr UNIQUE (usr),
	CONSTRAINT ck_authtokens_token CHECK (char_length(token) <= 512)
);
CREATE TRIGGER authtokens_trigger_01_change BEFORE UPDATE ON cms.authtokens
	FOR EACH ROW EXECUTE FUNCTION cms.update_changed_column();


CREATE TABLE cms.onetimetokens (
	token text NOT NULL,
	usr integer NOT NULL,
	created timestamp with time zone NOT NULL DEFAULT now(),
	CONSTRAINT pk_onetimetokens PRIMARY KEY (token),
	CONSTRAINT fk_onetimetokens_users FOREIGN KEY (usr)
		REFERENCES cms.users (usr),
	CONSTRAINT ck_ontimetokens_token CHECK (char_length(token) <= 512)
);


CREATE TABLE cms.loginsessions (
	hash text NOT NULL,
	usr integer NOT NULL,
	expires timestamp with time zone NOT NULL,
	CONSTRAINT pk_loginsessions PRIMARY KEY (hash),
	CONSTRAINT uc_loginsessions_usr UNIQUE (usr),
	CONSTRAINT fk_loginsessions_users FOREIGN KEY (usr) REFERENCES cms.users(usr),
	CONSTRAINT ck_loginsessions_hash CHECK (char_length(hash) <= 254)
);


CREATE TABLE cms.types (
	type integer GENERATED ALWAYS AS IDENTITY,
	handle text NOT NULL,
	kind cms.contenttype NOT NULL,
	CONSTRAINT pk_types PRIMARY KEY (type),
	CONSTRAINT uc_types_handle UNIQUE (handle),
	CONSTRAINT ck_types_handle CHECK (char_length(handle) <= 256)
);


CREATE TABLE cms.nodes (
	node integer GENERATED ALWAYS AS IDENTITY,
	uid text NOT NULL,
	parent integer,
	published boolean DEFAULT false NOT NULL,
	hidden boolean DEFAULT false NOT NULL,
	locked boolean DEFAULT false NOT NULL,
	type integer NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created timestamp with time zone NOT NULL DEFAULT now(),
	changed timestamp with time zone NOT NULL DEFAULT now(),
	deleted timestamp with time zone,
	content jsonb NOT NULL,
	CONSTRAINT pk_nodes PRIMARY KEY (node),
	CONSTRAINT uc_nodes_uid UNIQUE (uid),
	CONSTRAINT fk_nodes_users_creator FOREIGN KEY (creator)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_nodes_nodes FOREIGN KEY (parent)
		REFERENCES cms.nodes (node),
	CONSTRAINT fk_nodes_users_editor FOREIGN KEY (editor)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_nodes_types FOREIGN KEY (type)
		REFERENCES cms.types (type) ON UPDATE CASCADE ON DELETE NO ACTION,
	CONSTRAINT ck_nodes_uid CHECK (char_length(uid) <= 64)
);
CREATE INDEX ix_nodes_content ON cms.nodes USING GIN (type, content);
CREATE FUNCTION cms.process_nodes_audit()
	RETURNS TRIGGER AS $$
BEGIN
	INSERT INTO audit.nodes (
		node, parent, changed, published, hidden, locked,
		type, editor, deleted, content
	) VALUES (
		OLD.node, OLD.parent, OLD.changed, OLD.published, OLD.hidden, OLD.locked,
		OLD.type, OLD.editor, OLD.deleted, OLD.content
	);

	RETURN OLD;
EXCEPTION WHEN unique_violation THEN
	RAISE WARNING 'Duplicate nodes audit row skipped. node: %, changed: %', OLD.node, OLD.changed;
	RETURN NULL;
END;
$$ LANGUAGE plpgsql;
CREATE FUNCTION cms.check_if_deletable()
	RETURNS TRIGGER AS $$
BEGIN
	IF (
		NEW.deleted IS NOT NULL
		AND (
			SELECT count(*)
			FROM cms.menuitems mi
			WHERE
				mi.data->>'type' = 'node'
				AND mi.data->>'node' = OLD.node::text
		) > 0
	)
	THEN
		RAISE EXCEPTION 'node is still referenced in a menu';
	END IF;

	RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER nodes_trigger_01_delete BEFORE UPDATE ON cms.nodes
	FOR EACH ROW EXECUTE PROCEDURE cms.check_if_deletable();
CREATE TRIGGER nodes_trigger_02_change BEFORE UPDATE ON cms.nodes
	FOR EACH ROW EXECUTE FUNCTION cms.update_changed_column();
CREATE TRIGGER nodes_trigger_03_audit AFTER UPDATE
	ON cms.nodes FOR EACH ROW EXECUTE PROCEDURE
	cms.process_nodes_audit();


CREATE TABLE cms.fulltext (
	node integer NOT NULL,
	locale text NOT NULL,
	document tsvector NOT NULL,
	CONSTRAINT pk_fulltext PRIMARY KEY (node, locale),
	CONSTRAINT fk_fulltext_nodes FOREIGN KEY (node)
		REFERENCES cms.nodes (node),
	CONSTRAINT ck_fulltext_locale CHECK (char_length(locale) <= 32)
);
CREATE INDEX ix_nodes_tsv ON cms.fulltext USING GIN(document);


CREATE TABLE cms.urlpaths (
	node integer NOT NULL,
	path text NOT NULL,
	locale text NOT NULL,
	creator integer NOT NULL,
	editor integer NOT NULL,
	created timestamp with time zone NOT NULL DEFAULT now(),
	inactive timestamp with time zone,
	CONSTRAINT pk_urlpaths PRIMARY KEY (node, locale, path),
	CONSTRAINT fk_urlpaths_nodes FOREIGN KEY (node)
		REFERENCES cms.nodes (node),
	CONSTRAINT fk_urlpaths_users_creator FOREIGN KEY (creator)
		REFERENCES cms.users (usr),
	CONSTRAINT fk_urlpaths_users_editor FOREIGN KEY (editor)
		REFERENCES cms.users (usr),
	CONSTRAINT ck_urlpaths_path CHECK (char_length(path) <= 512),
	CONSTRAINT ck_urlpaths_locale CHECK (char_length(locale) <= 32)
);
CREATE UNIQUE INDEX ux_urlpaths_path ON cms.urlpaths
	USING btree (path);
CREATE UNIQUE INDEX ux_urlpaths_locale ON cms.urlpaths
	USING btree (node, locale) WHERE (inactive IS NULL);


CREATE TABLE cms.drafts (
	node integer NOT NULL,
	changed timestamp with time zone NOT NULL,
	editor integer NOT NULL,
	content jsonb NOT NULL,
	CONSTRAINT pk_drafts PRIMARY KEY (node),
	CONSTRAINT fk_drafts_nodes FOREIGN KEY (node) REFERENCES cms.nodes (node)
);
CREATE FUNCTION cms.process_drafts_audit()
	RETURNS TRIGGER AS $$
BEGIN
	INSERT INTO audit.drafts (
		node, changed, editor, content
	) VALUES (
		OLD.node, OLD.changed, OLD.editor, OLD.content
	);

	RETURN OLD;
EXCEPTION WHEN unique_violation THEN
	RAISE WARNING 'Duplicate drafts audit row skipped. draft: %, changed: %', OLD.node, OLD.changed;
	RETURN NULL;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER drafts_trigger_01_audit AFTER UPDATE
	ON cms.drafts FOR EACH ROW EXECUTE PROCEDURE
	cms.process_drafts_audit();


CREATE TABLE cms.menus (
	menu text NOT NULL,
	description text NOT NULL,
	CONSTRAINT pk_menus PRIMARY KEY (menu),
	CONSTRAINT ck_menus_menu CHECK (char_length(menu) <= 32),
	CONSTRAINT ck_menus_description CHECK (char_length(description) <= 128)
);


CREATE TABLE cms.menuitems (
	item text NOT NULL,
	parent text,
	menu text NOT NULL,
	displayorder smallint NOT NULL,
	data jsonb NOT NULL,
	CONSTRAINT pk_menuitems PRIMARY KEY (item),
	CONSTRAINT fk_menuitems_menus FOREIGN KEY (menu)
		REFERENCES cms.menus (menu) ON UPDATE CASCADE,
	CONSTRAINT fk_menuitems_menuitems FOREIGN KEY (parent)
		REFERENCES cms.menuitems (item),
	CONSTRAINT ck_menuitems_item CHECK (char_length(item) <= 64),
	CONSTRAINT ck_menuitems_parent CHECK (char_length(parent) <= 64)
);


CREATE TABLE cms.topics (
	topic integer GENERATED ALWAYS AS IDENTITY,
	uid text NOT NULL,
	name jsonb NOT NULL,
	color text NOT NULL,
	CONSTRAINT pk_topics PRIMARY KEY (topic),
	CONSTRAINT uc_topics_uid UNIQUE (uid),
	CONSTRAINT ck_topics_uid CHECK (char_length(uid) <= 64),
	CONSTRAINT ck_topics_color CHECK (char_length(color) <= 128)
);


CREATE TABLE cms.tags (
	tag integer GENERATED ALWAYS AS IDENTITY,
	uid text NOT NULL,
	name jsonb NOT NULL,
	topic integer NOT NULL,
	CONSTRAINT pk_tags PRIMARY KEY (tag),
	CONSTRAINT uc_tags_uid UNIQUE (uid),
	CONSTRAINT fk_tags_topics FOREIGN KEY (topic)
		REFERENCES cms.topics (topic),
	CONSTRAINT ck_tags_uid CHECK (char_length(uid) <= 64)
);


CREATE TABLE cms.nodetags (
	node integer NOT NULL,
	tag integer NOT NULL,
	sort smallint NOT NULL DEFAULT 0,
	CONSTRAINT pk_nodetags PRIMARY KEY (node, tag),
	CONSTRAINT fk_nodetags_nodes FOREIGN KEY (node)
		REFERENCES cms.nodes (node),
	CONSTRAINT fk_nodetags_tags FOREIGN KEY (tag)
		REFERENCES cms.tags (tag)
);


CREATE TABLE audit.nodes (
	node integer NOT NULL,
	parent integer,
	changed timestamp with time zone NOT NULL,
	published boolean NOT NULL,
	hidden boolean NOT NULL,
	locked boolean NOT NULL,
	type text NOT NULL,
	editor integer NOT NULL,
	deleted timestamp with time zone,
	content jsonb NOT NULL,
	CONSTRAINT pk_nodes PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_nodes FOREIGN KEY (node)
		REFERENCES cms.nodes (node)
);


CREATE TABLE audit.drafts (
	node integer NOT NULL,
	changed timestamp with time zone NOT NULL,
	editor integer NOT NULL,
	content jsonb NOT NULL,
	CONSTRAINT pk_drafts PRIMARY KEY (node, changed),
	CONSTRAINT fk_audit_drafts FOREIGN KEY (node)
		REFERENCES cms.drafts (node)
);


CREATE TABLE audit.users (
	usr integer NOT NULL,
	username text,
	email text,
	pwhash text NOT NULL,
	userrole text NOT NULL,
	active boolean NOT NULL,
	data jsonb NOT NULL,
	editor integer NOT NULL,
	changed timestamp with time zone NOT NULL DEFAULT now(),
	deleted timestamp with time zone,
	CONSTRAINT pk_users PRIMARY KEY (usr, changed),
	CONSTRAINT fk_audit_users FOREIGN KEY (usr)
		REFERENCES cms.users (usr)
);
