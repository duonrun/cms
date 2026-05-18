ALTER TABLE audit.users DROP CONSTRAINT IF EXISTS fk_audit_users;
ALTER TABLE audit.drafts DROP CONSTRAINT IF EXISTS fk_audit_drafts;
ALTER TABLE audit.nodes DROP CONSTRAINT IF EXISTS fk_audit_nodes;
ALTER TABLE cms.nodetags DROP CONSTRAINT IF EXISTS fk_nodetags_tags;
ALTER TABLE cms.nodetags DROP CONSTRAINT IF EXISTS fk_nodetags_nodes;
ALTER TABLE cms.tags DROP CONSTRAINT IF EXISTS fk_tags_topics;
ALTER TABLE cms.drafts DROP CONSTRAINT IF EXISTS fk_drafts_nodes;
ALTER TABLE cms.urlpaths DROP CONSTRAINT IF EXISTS fk_urlpaths_users_editor;
ALTER TABLE cms.urlpaths DROP CONSTRAINT IF EXISTS fk_urlpaths_users_creator;
ALTER TABLE cms.urlpaths DROP CONSTRAINT IF EXISTS fk_urlpaths_nodes;
ALTER TABLE cms.fulltext DROP CONSTRAINT IF EXISTS fk_fulltext_nodes;
ALTER TABLE cms.nodes DROP CONSTRAINT IF EXISTS fk_nodes_types;
ALTER TABLE cms.nodes DROP CONSTRAINT IF EXISTS fk_nodes_users_editor;
ALTER TABLE cms.nodes DROP CONSTRAINT IF EXISTS fk_nodes_nodes;
ALTER TABLE cms.nodes DROP CONSTRAINT IF EXISTS fk_nodes_users_creator;
ALTER TABLE cms.loginsessions DROP CONSTRAINT IF EXISTS fk_loginsessions_users;
ALTER TABLE cms.onetimetokens DROP CONSTRAINT IF EXISTS fk_onetimetokens_users;
ALTER TABLE cms.authtokens DROP CONSTRAINT IF EXISTS fk_authtokens_users_editor;
ALTER TABLE cms.authtokens DROP CONSTRAINT IF EXISTS fk_authtokens_users_creator;
ALTER TABLE cms.authtokens DROP CONSTRAINT IF EXISTS fk_authtokens_users;
ALTER TABLE cms.users DROP CONSTRAINT IF EXISTS fk_users_users_editor;
ALTER TABLE cms.users DROP CONSTRAINT IF EXISTS fk_users_users_creator;

ALTER TABLE cms.users
	ALTER COLUMN usr TYPE bigint,
	ALTER COLUMN creator TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE cms.authtokens
	ALTER COLUMN usr TYPE bigint,
	ALTER COLUMN creator TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE cms.onetimetokens
	ALTER COLUMN usr TYPE bigint;
ALTER TABLE cms.loginsessions
	ALTER COLUMN usr TYPE bigint;
ALTER TABLE cms.types
	ALTER COLUMN "type" TYPE bigint;
ALTER TABLE cms.nodes
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN parent TYPE bigint,
	ALTER COLUMN "type" TYPE bigint,
	ALTER COLUMN creator TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE cms.fulltext
	ALTER COLUMN node TYPE bigint;
ALTER TABLE cms.urlpaths
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN creator TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE cms.drafts
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE cms.menuitems
	ALTER COLUMN displayorder TYPE integer;
ALTER TABLE cms.topics
	ALTER COLUMN topic TYPE bigint;
ALTER TABLE cms.tags
	ALTER COLUMN tag TYPE bigint,
	ALTER COLUMN topic TYPE bigint;
ALTER TABLE cms.nodetags
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN tag TYPE bigint,
	ALTER COLUMN sort TYPE integer;
ALTER TABLE audit.nodes
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN parent TYPE bigint,
	ALTER COLUMN "type" TYPE bigint USING "type"::bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE audit.drafts
	ALTER COLUMN node TYPE bigint,
	ALTER COLUMN editor TYPE bigint;
ALTER TABLE audit.users
	ALTER COLUMN usr TYPE bigint,
	ALTER COLUMN editor TYPE bigint;

DO $$
DECLARE
	sequence_name text;
BEGIN
	FOREACH sequence_name IN ARRAY ARRAY[
		pg_get_serial_sequence('cms.users', 'usr'),
		pg_get_serial_sequence('cms.types', 'type'),
		pg_get_serial_sequence('cms.nodes', 'node'),
		pg_get_serial_sequence('cms.topics', 'topic'),
		pg_get_serial_sequence('cms.tags', 'tag')
	] LOOP
		IF sequence_name IS NOT NULL THEN
			EXECUTE format('ALTER SEQUENCE %s AS bigint', sequence_name::regclass);
		END IF;
	END LOOP;
END;
$$;

ALTER TABLE cms.users ADD CONSTRAINT fk_users_users_creator FOREIGN KEY (creator)
	REFERENCES cms.users (usr);
ALTER TABLE cms.users ADD CONSTRAINT fk_users_users_editor FOREIGN KEY (editor)
	REFERENCES cms.users (usr);

ALTER TABLE cms.authtokens ADD CONSTRAINT fk_authtokens_users FOREIGN KEY (usr)
	REFERENCES cms.users (usr);
ALTER TABLE cms.authtokens ADD CONSTRAINT fk_authtokens_users_creator FOREIGN KEY (creator)
	REFERENCES cms.users (usr);
ALTER TABLE cms.authtokens ADD CONSTRAINT fk_authtokens_users_editor FOREIGN KEY (editor)
	REFERENCES cms.users (usr);

ALTER TABLE cms.onetimetokens ADD CONSTRAINT fk_onetimetokens_users FOREIGN KEY (usr)
	REFERENCES cms.users (usr);

ALTER TABLE cms.loginsessions ADD CONSTRAINT fk_loginsessions_users FOREIGN KEY (usr)
	REFERENCES cms.users (usr);

ALTER TABLE cms.nodes ADD CONSTRAINT fk_nodes_users_creator FOREIGN KEY (creator)
	REFERENCES cms.users (usr);
ALTER TABLE cms.nodes ADD CONSTRAINT fk_nodes_nodes FOREIGN KEY (parent)
	REFERENCES cms.nodes (node);
ALTER TABLE cms.nodes ADD CONSTRAINT fk_nodes_users_editor FOREIGN KEY (editor)
	REFERENCES cms.users (usr);
ALTER TABLE cms.nodes ADD CONSTRAINT fk_nodes_types FOREIGN KEY ("type")
	REFERENCES cms.types ("type") ON UPDATE CASCADE ON DELETE NO ACTION;

ALTER TABLE cms.fulltext ADD CONSTRAINT fk_fulltext_nodes FOREIGN KEY (node)
	REFERENCES cms.nodes (node);

ALTER TABLE cms.urlpaths ADD CONSTRAINT fk_urlpaths_nodes FOREIGN KEY (node)
	REFERENCES cms.nodes (node);
ALTER TABLE cms.urlpaths ADD CONSTRAINT fk_urlpaths_users_creator FOREIGN KEY (creator)
	REFERENCES cms.users (usr);
ALTER TABLE cms.urlpaths ADD CONSTRAINT fk_urlpaths_users_editor FOREIGN KEY (editor)
	REFERENCES cms.users (usr);

ALTER TABLE cms.drafts ADD CONSTRAINT fk_drafts_nodes FOREIGN KEY (node)
	REFERENCES cms.nodes (node);

ALTER TABLE cms.tags ADD CONSTRAINT fk_tags_topics FOREIGN KEY (topic)
	REFERENCES cms.topics (topic);

ALTER TABLE cms.nodetags ADD CONSTRAINT fk_nodetags_nodes FOREIGN KEY (node)
	REFERENCES cms.nodes (node);
ALTER TABLE cms.nodetags ADD CONSTRAINT fk_nodetags_tags FOREIGN KEY (tag)
	REFERENCES cms.tags (tag);

ALTER TABLE audit.nodes ADD CONSTRAINT fk_audit_nodes FOREIGN KEY (node)
	REFERENCES cms.nodes (node);

ALTER TABLE audit.drafts ADD CONSTRAINT fk_audit_drafts FOREIGN KEY (node)
	REFERENCES cms.drafts (node);

ALTER TABLE audit.users ADD CONSTRAINT fk_audit_users FOREIGN KEY (usr)
	REFERENCES cms.users (usr);
