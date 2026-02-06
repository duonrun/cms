-- SQLite update migration: named constraints
-- This is a no-op for SQLite because the install DDL was created with
-- correctly named constraints from the start.
-- PostgreSQL needed this migration to rename constraints created with
-- auto-generated names.
SELECT 1;
