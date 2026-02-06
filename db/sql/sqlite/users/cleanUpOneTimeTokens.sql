DELETE FROM cms_onetimetokens WHERE created < datetime('now', '-5 minutes');
