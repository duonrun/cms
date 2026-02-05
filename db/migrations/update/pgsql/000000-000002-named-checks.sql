ALTER TABLE cms.users DROP CONSTRAINT users_uid_check;
ALTER TABLE cms.users ADD CONSTRAINT ck_users_uid CHECK (char_length(uid) <= 64);
ALTER TABLE cms.users DROP CONSTRAINT users_username_check;
ALTER TABLE cms.users ADD CONSTRAINT ck_users_username CHECK
    (username IS NULL OR (char_length(username) > 0 AND char_length(username) <= 64));
ALTER TABLE cms.users DROP CONSTRAINT users_email_check;
ALTER TABLE cms.users ADD CONSTRAINT ck_users_email CHECK
    (email IS NULL OR (email SIMILAR TO '%@%' AND char_length(email) >= 5 AND char_length(email) <= 256));
ALTER TABLE cms.users ADD CONSTRAINT ck_users_username_or_email CHECK (username IS NOT NULL OR email IS NOT NULL);
DROP INDEX IF EXISTS cms.uix_users_username;
CREATE UNIQUE INDEX ux_users_username ON cms.users
	USING btree (lower(username)) WHERE (deleted IS NULL AND username IS NOT NULL);
DROP INDEX IF EXISTS cms.uix_users_email;
CREATE UNIQUE INDEX ux_users_email ON cms.users
	USING btree (lower(email)) WHERE (deleted IS NULL AND email IS NOT NULL);

ALTER TABLE cms.authtokens DROP CONSTRAINT authtokens_token_check;
ALTER TABLE cms.authtokens ADD CONSTRAINT ck_authtokens_token CHECK (char_length(token) <= 512);

ALTER TABLE cms.onetimetokens DROP CONSTRAINT onetimetokens_token_check;
ALTER TABLE cms.onetimetokens ADD CONSTRAINT ck_ontimetokens_token CHECK (char_length(token) <= 512);

ALTER TABLE cms.types DROP CONSTRAINT types_handle_check;
ALTER TABLE cms.types ADD CONSTRAINT ck_types_handle CHECK (char_length(handle) <= 256);

ALTER TABLE cms.nodes DROP CONSTRAINT nodes_uid_check;
ALTER TABLE cms.nodes ADD CONSTRAINT ck_nodes_uid CHECK (char_length(uid) <= 64);

ALTER TABLE cms.fulltext DROP CONSTRAINT fulltext_locale_check;
ALTER TABLE cms.fulltext ADD CONSTRAINT ck_fulltext_locale CHECK (char_length(locale) <= 32);

ALTER TABLE cms.urlpaths DROP CONSTRAINT urlpaths_path_check;
ALTER TABLE cms.urlpaths ADD CONSTRAINT ck_urlpaths_path CHECK (char_length(path) <= 512);
ALTER TABLE cms.urlpaths DROP CONSTRAINT urlpaths_locale_check;
ALTER TABLE cms.urlpaths ADD CONSTRAINT ck_urlpaths_locale CHECK (char_length(locale) <= 32);
DROP INDEX IF EXISTS cms.uix_urlpaths_path;
CREATE UNIQUE INDEX ux_urlpaths_path ON cms.urlpaths
	USING btree (path);
DROP INDEX IF EXISTS cms.uix_urlpaths_locale;
CREATE UNIQUE INDEX ux_urlpaths_locale ON cms.urlpaths
	USING btree (node, locale) WHERE (inactive IS NULL);

ALTER TABLE cms.menus DROP CONSTRAINT menus_menu_check;
ALTER TABLE cms.menus ADD CONSTRAINT ck_menus_menu CHECK (char_length(menu) <= 32);
ALTER TABLE cms.menus DROP CONSTRAINT menus_description_check;
ALTER TABLE cms.menus ADD CONSTRAINT ck_menus_description CHECK (char_length(description) <= 128);

ALTER TABLE cms.menuitems DROP CONSTRAINT menuitems_item_check;
ALTER TABLE cms.menuitems ADD CONSTRAINT ck_menuitems_item CHECK (char_length(item) <= 64);
ALTER TABLE cms.menuitems DROP CONSTRAINT menuitems_parent_check;
ALTER TABLE cms.menuitems ADD CONSTRAINT ck_menuitems_parent CHECK (char_length(parent) <= 64);

ALTER TABLE cms.topics DROP CONSTRAINT topics_uid_check;
ALTER TABLE cms.topics ADD CONSTRAINT ck_topics_uid CHECK (char_length(uid) <= 64);
ALTER TABLE cms.topics DROP CONSTRAINT topics_color_check;
ALTER TABLE cms.topics ADD CONSTRAINT ck_topics_color CHECK (char_length(color) <= 128);


ALTER TABLE cms.tags DROP CONSTRAINT tags_uid_check;
ALTER TABLE cms.tags ADD CONSTRAINT ck_tags_uid CHECK (char_length(uid) <= 64)
