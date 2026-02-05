-- SQLite placeholder: 000000-000003-fix-authtokens-trigger
--
-- This migration fixes the authtokens trigger in PostgreSQL (was incorrectly
-- attached to cms.users instead of cms.authtokens).
-- For SQLite, the install DDL (000000-000000-init-ddl.sql) was created after
-- this bug was discovered and already has the trigger on the correct table.
--
-- No action needed for SQLite.
SELECT 1;
