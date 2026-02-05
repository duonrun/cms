<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

use Duon\Cms\Exception\RuntimeException;

final class SqliteDialect implements SqlDialect
{
	public function driver(): string
	{
		return 'sqlite';
	}

	public function table(string $name): string
	{
		if (str_contains($name, '.')) {
			return str_replace('.', '_', $name);
		}

		return 'cms_' . $name;
	}

	public function jsonExtract(string $column, string $path, bool $text = true): string
	{
		$parts = explode('.', $path);
		$count = count($parts);

		if ($count === 1) {
			return "json_extract({$column}, '\$.{$parts[0]}.value')";
		}

		$jsonPath = implode('.', $parts);

		return "json_extract({$column}, '\$.{$jsonPath}')";
	}

	public function jsonFieldCompare(
		string $column,
		string $path,
		string $operator,
		mixed $value,
		string $placeholder,
	): array {
		if ($this->isWildcardPath($path)) {
			$basePath = $this->stripWildcard($path);
			$source = "json_each({$column}, '\$.{$basePath}')";
			$sql = "EXISTS (SELECT 1 FROM {$source} WHERE value {$operator} {$placeholder})";

			return [
				'sql' => $sql,
				'paramValue' => $value,
			];
		}

		$field = "json_extract({$column}, '\$.{$path}')";

		return [
			'sql' => "{$field} {$operator} {$placeholder}",
			'paramValue' => $value,
		];
	}

	public function jsonFieldRegex(
		string $column,
		string $path,
		string $pattern,
		bool $ignoreCase,
		bool $negate,
		string $placeholder,
	): array {
		if ($this->isWildcardPath($path)) {
			$basePath = $this->stripWildcard($path);
			$source = "json_each({$column}, '\$.{$basePath}')";
			$expr = $ignoreCase
				? "regexp_i(value, {$placeholder})"
				: "value REGEXP {$placeholder}";
			$condition = $negate ? "NOT ({$expr})" : $expr;
			$sql = "EXISTS (SELECT 1 FROM {$source} WHERE {$condition})";

			return [
				'sql' => $sql,
				'paramValue' => $pattern,
			];
		}

		$field = "json_extract({$column}, '\$.{$path}')";

		if ($ignoreCase) {
			$expr = "regexp_i({$field}, {$placeholder})";
		} else {
			$expr = "{$field} REGEXP {$placeholder}";
		}

		return [
			'sql' => $negate ? "NOT ({$expr})" : $expr,
			'paramValue' => $pattern,
		];
	}

	private function isWildcardPath(string $path): bool
	{
		return str_ends_with($path, '.*');
	}

	private function stripWildcard(string $path): string
	{
		return substr($path, 0, -2);
	}

	public function jsonPathExists(string $column, string $path): string
	{
		return "json_extract({$column}, '\$.{$path}') IS NOT NULL";
	}

	public function like(string $left, string $right): string
	{
		return "{$left} LIKE {$right}";
	}

	public function ilike(string $left, string $right): string
	{
		return "{$left} LIKE {$right} COLLATE NOCASE";
	}

	public function unlike(string $left, string $right): string
	{
		return "{$left} NOT LIKE {$right}";
	}

	public function iunlike(string $left, string $right): string
	{
		return "{$left} NOT LIKE {$right} COLLATE NOCASE";
	}

	public function regex(string $left, string $right): string
	{
		return "{$left} REGEXP {$right}";
	}

	public function iregex(string $left, string $right): string
	{
		return "regexp_i({$left}, {$right})";
	}

	public function notRegex(string $left, string $right): string
	{
		return "NOT ({$left} REGEXP {$right})";
	}

	public function notIregex(string $left, string $right): string
	{
		return "NOT regexp_i({$left}, {$right})";
	}

	public function fulltext(string $document, string $query): string
	{
		throw new RuntimeException('Fulltext predicate not implemented for SQLite dialect.');
	}

	public function now(): string
	{
		return "strftime('%Y-%m-%d %H:%M:%S', 'now')";
	}
}
