<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

/**
 * SQLite dialect implementation.
 *
 * Uses flattened table names (cms_nodes) and SQLite-specific
 * JSON functions (json_extract) and pattern matching.
 *
 * Note: Regex support requires registering a REGEXP function via
 * PDO::sqliteCreateFunction() before use.
 */
final readonly class SqliteDialect implements SqlDialect
{
	public function driver(): string
	{
		return 'sqlite';
	}

	public function table(string $schema, string $table): string
	{
		return "{$schema}_{$table}";
	}

	public function jsonExtractText(string $column, string $path): string
	{
		// SQLite json_extract always returns the value in its native type
		// For text comparison, we may need to cast, but usually it works
		return "json_extract({$column}, '\$.{$path}')";
	}

	public function jsonExtract(string $column, string $path): string
	{
		return "json_extract({$column}, '\$.{$path}')";
	}

	public function like(string $column, string $paramName): string
	{
		return "{$column} LIKE {$paramName}";
	}

	public function ilike(string $column, string $paramName): string
	{
		// SQLite LIKE is case-insensitive for ASCII by default
		// For Unicode, we need COLLATE NOCASE
		return "{$column} LIKE {$paramName} COLLATE NOCASE";
	}

	public function regex(string $column, string $paramName): string
	{
		// Requires REGEXP function to be registered via sqliteCreateFunction
		return "{$column} REGEXP {$paramName}";
	}

	public function iregex(string $column, string $paramName): string
	{
		// Case-insensitive regex - the REGEXP function implementation
		// should handle the 'i' flag or we use a separate function
		return "regexp_i({$column}, {$paramName})";
	}

	public function jsonExists(string $column, string $path): string
	{
		// SQLite: json_type returns NULL if path doesn't exist
		return "json_type({$column}, '\$.{$path}') IS NOT NULL";
	}

	public function now(): string
	{
		// SQLite uses different datetime functions
		return "datetime('now')";
	}
}
