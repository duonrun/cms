CREATE INDEX IF NOT EXISTS ix_users_login_username ON cms_users (username)
	WHERE (deleted IS NULL AND active = 1 AND userrole != 'system' AND username IS NOT NULL);
CREATE INDEX IF NOT EXISTS ix_users_login_email ON cms_users (email)
	WHERE (deleted IS NULL AND active = 1 AND userrole != 'system' AND email IS NOT NULL);

CREATE INDEX IF NOT EXISTS ix_onetimetokens_created ON cms_onetimetokens (created);

CREATE INDEX IF NOT EXISTS ix_nodes_type ON cms_nodes (type);
CREATE INDEX IF NOT EXISTS ix_nodes_visibility ON cms_nodes (published, hidden, type)
	WHERE (deleted IS NULL);
