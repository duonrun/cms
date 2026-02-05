<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

/**
 * PostgreSQL dialect implementation.
 *
 * Uses schema-qualified table names (cms.nodes) and PostgreSQL-specific
 * JSON operators (->, ->>, @@) and pattern matching (ILIKE, ~, ~*).
 */
final readonly class PostgresDialect implements SqlDialect
{
	public function driver(): string
	{
		return 'pgsql';
	}

	public function table(string $schema, string $table): string
	{
		return "{$schema}.{$table}";
	}

	public function jsonExtractText(string $column, string $path): string
	{
		$parts = explode('.', $path);

		if (count($parts) === 1) {
			return "{$column}->>'{$path}'";
		}

		// Navigate with -> for all but the last segment, then ->> for text
		$navigation = '';
		$lastIndex = count($parts) - 1;

		foreach ($parts as $index => $part) {
			if ($index === $lastIndex) {
				$navigation .= "->>'{$part}'";
			} else {
				$navigation .= "->'{$part}'";
			}
		}

		return $column . $navigation;
	}

	public function jsonExtract(string $column, string $path): string
	{
		$parts = explode('.', $path);

		if (count($parts) === 1) {
			return "{$column}->'{$path}'";
		}

		$navigation = '';
		foreach ($parts as $part) {
			$navigation .= "->'{$part}'";
		}

		return $column . $navigation;
	}

	public function like(string $column, string $paramName): string
	{
		return "{$column} LIKE {$paramName}";
	}

	public function unlike(string $column, string $paramName): string
	{
		return "{$column} NOT LIKE {$paramName}";
	}

	public function ilike(string $column, string $paramName): string
	{
		return "{$column} ILIKE {$paramName}";
	}

	public function iunlike(string $column, string $paramName): string
	{
		return "{$column} NOT ILIKE {$paramName}";
	}

	public function regex(string $column, string $paramName): string
	{
		return "{$column} ~ {$paramName}";
	}

	public function iregex(string $column, string $paramName): string
	{
		return "{$column} ~* {$paramName}";
	}

	public function jsonExists(string $column, string $path): string
	{
		// PostgreSQL: use jsonb_exists() function instead of ? operator
		// The ? operator conflicts with PDO's positional parameter placeholder
		if (!str_contains($path, '.')) {
			return "jsonb_exists({$column}, '{$path}')";
		}

		// For nested paths, check if extraction is not null
		return $this->jsonExtract($column, $path) . ' IS NOT NULL';
	}

	public function jsonWildcardMatch(
		string $column,
		string $basePath,
		string $operator,
		string $paramOrValue,
	): string {
		// Check if this is a LIKE/ILIKE operator
		$upperOp = strtoupper($operator);
		if (in_array($upperOp, ['LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE'], true)) {
			// Use jsonb_each_text for LIKE patterns since jsonpath doesn't support them
			return "EXISTS (SELECT 1 FROM jsonb_each_text({$column}->'{$basePath}'->>'value'::jsonb) WHERE value {$operator} {$paramOrValue})";
		}

		// For comparison operators, use jsonpath with @@ operator
		$jsonOperator = match ($operator) {
			'=' => '==',
			'!=' => '!=',
			'<' => '<',
			'>' => '>',
			'<=' => '<=',
			'>=' => '>=',
			default => '==',
		};

		// Use jsonpath: $.field.value.* == "value"
		return "{$column} @@ '\$.{$basePath}.* {$jsonOperator} {$paramOrValue}'";
	}

	public function now(): string
	{
		return 'NOW()';
	}

	public function fulltext(string $nodeColumn, string $paramName, ?string $locale = null): string
	{
		$localeCondition = $locale !== null
			? " AND f.locale = '{$locale}'"
			: '';

		return "{$nodeColumn} IN (SELECT f.node FROM cms.fulltext f WHERE f.document @@ websearch_to_tsquery('english', {$paramName}){$localeCondition})";
	}
}
