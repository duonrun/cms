<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

/**
 * SQL dialect interface for database-specific query generation.
 *
 * Provides driver-specific SQL syntax for:
 * - Table name resolution (schema-qualified vs flattened)
 * - JSON field access
 * - Pattern matching operators (LIKE, ILIKE, regex)
 * - Fulltext search (when enabled)
 */
interface SqlDialect
{
	/**
	 * Get the PDO driver name for this dialect.
	 *
	 * @return string 'pgsql', 'sqlite', or future driver names
	 */
	public function driver(): string;

	/**
	 * Resolve a table name for this dialect.
	 *
	 * PostgreSQL: Returns schema-qualified name (e.g., 'cms.nodes')
	 * SQLite: Returns flattened name (e.g., 'cms_nodes')
	 *
	 * @param string $schema The schema name (e.g., 'cms', 'audit')
	 * @param string $table The table name (e.g., 'nodes', 'users')
	 */
	public function table(string $schema, string $table): string;

	/**
	 * Generate SQL for extracting a JSON field value as text.
	 *
	 * @param string $column The JSON column (e.g., 'n.content')
	 * @param string $path The JSON path (e.g., 'title.value.en')
	 * @return string SQL expression that extracts the value as text
	 */
	public function jsonExtractText(string $column, string $path): string;

	/**
	 * Generate SQL for extracting a JSON field value preserving type.
	 *
	 * @param string $column The JSON column (e.g., 'n.content')
	 * @param string $path The JSON path (e.g., 'active')
	 * @return string SQL expression that extracts the value
	 */
	public function jsonExtract(string $column, string $path): string;

	/**
	 * Generate SQL for case-sensitive LIKE comparison.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for LIKE
	 */
	public function like(string $column, string $paramName): string;

	/**
	 * Generate SQL for case-sensitive NOT LIKE comparison.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for NOT LIKE
	 */
	public function unlike(string $column, string $paramName): string;

	/**
	 * Generate SQL for case-insensitive LIKE comparison.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for case-insensitive LIKE
	 */
	public function ilike(string $column, string $paramName): string;

	/**
	 * Generate SQL for case-insensitive NOT LIKE comparison.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for case-insensitive NOT LIKE
	 */
	public function iunlike(string $column, string $paramName): string;

	/**
	 * Generate SQL for case-sensitive regex match.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for regex match
	 */
	public function regex(string $column, string $paramName): string;

	/**
	 * Generate SQL for case-insensitive regex match.
	 *
	 * @param string $column The column or expression to match
	 * @param string $paramName The parameter placeholder (e.g., ':p0')
	 * @return string SQL expression for case-insensitive regex match
	 */
	public function iregex(string $column, string $paramName): string;

	/**
	 * Generate SQL for checking if a JSON field exists.
	 *
	 * @param string $column The JSON column (e.g., 'n.content')
	 * @param string $path The JSON path to check (e.g., 'title')
	 * @return string SQL expression that returns true if field exists
	 */
	public function jsonExists(string $column, string $path): string;

	/**
	 * Generate SQL for matching any value in a JSON object (wildcard locale).
	 *
	 * Used for queries like `field.* = "value"` which should match if ANY
	 * locale value equals the specified value.
	 *
	 * @param string $column The JSON column (e.g., 'n.content')
	 * @param string $basePath The base JSON path without wildcard (e.g., 'title.value')
	 * @param string $operator The SQL comparison operator (e.g., '=', '!=', 'LIKE')
	 * @param string $paramOrValue The parameter placeholder or literal value
	 * @return string SQL expression for wildcard match
	 */
	public function jsonWildcardMatch(
		string $column,
		string $basePath,
		string $operator,
		string $paramOrValue,
	): string;

	/**
	 * Get the SQL function for current timestamp.
	 *
	 * @return string SQL expression for current timestamp (e.g., 'NOW()')
	 */
	public function now(): string;
}
