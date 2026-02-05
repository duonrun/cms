-- Test users for integration testing (SQLite)
-- Password for all users: 'password'

-- Test superuser
INSERT OR IGNORE INTO cms_users (
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
    'test-superuser',
    'test-superuser',
    'superuser@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$ZGZuVmhYbTlwZ0g0VjNkSg$xVLvB0L8B9Gm6F8aB5vBxQ0L8B9Gm6F8aB5vBxQ0L8B',
    'superuser',
    1,
    '{"name":"Test Superuser"}',
    1,
    1
);

-- Test admin user
INSERT OR IGNORE INTO cms_users (
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
    'test-admin',
    'test-admin',
    'admin@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$ZGZuVmhYbTlwZ0g0VjNkSg$xVLvB0L8B9Gm6F8aB5vBxQ0L8B9Gm6F8aB5vBxQ0L8B',
    'admin',
    1,
    '{"name":"Test Admin"}',
    1,
    1
);

-- Test editor user
INSERT OR IGNORE INTO cms_users (
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
    'test-editor',
    'test-editor',
    'editor@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$ZGZuVmhYbTlwZ0g0VjNkSg$xVLvB0L8B9Gm6F8aB5vBxQ0L8B9Gm6F8aB5vBxQ0L8B',
    'editor',
    1,
    '{"name":"Test Editor"}',
    1,
    1
);
