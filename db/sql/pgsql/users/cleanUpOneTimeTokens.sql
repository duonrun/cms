DELETE FROM cms.onetimetokens WHERE created < now()::time - INTERVAL '5 min';
