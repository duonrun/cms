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
		throw new RuntimeException('JSON extraction not implemented for SQLite dialect.');
	}

	public function like(string $left, string $right): string
	{
		throw new RuntimeException('LIKE operator not implemented for SQLite dialect.');
	}

	public function ilike(string $left, string $right): string
	{
		throw new RuntimeException('ILIKE operator not implemented for SQLite dialect.');
	}

	public function regex(string $left, string $right): string
	{
		throw new RuntimeException('Regex operator not implemented for SQLite dialect.');
	}

	public function iregex(string $left, string $right): string
	{
		throw new RuntimeException('Regex operator not implemented for SQLite dialect.');
	}

	public function fulltext(string $document, string $query): string
	{
		throw new RuntimeException('Fulltext predicate not implemented for SQLite dialect.');
	}
}
