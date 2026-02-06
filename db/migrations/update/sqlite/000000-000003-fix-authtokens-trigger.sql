-- SQLite update migration: fix authtokens trigger
-- This is a no-op for SQLite because the install DDL was created with
-- the correct trigger target (cms_authtokens) from the start.
-- PostgreSQL needed this migration to fix a trigger that was incorrectly
-- attached to cms.users instead of cms.authtokens.
SELECT 1;
