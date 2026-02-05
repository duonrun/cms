<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

use Duon\Cms\Exception\RuntimeException;

final class PostgresDialect implements SqlDialect
{
	public function driver(): string
	{
		return 'pgsql';
	}

	public function table(string $name): string
	{
		if (str_contains($name, '.')) {
			return $name;
		}

		return 'cms.' . $name;
	}

	public function jsonExtract(string $column, string $path, bool $text = true): string
	{
		throw new RuntimeException('JSON extraction not implemented for PostgreSQL dialect.');
	}

	public function like(string $left, string $right): string
	{
		throw new RuntimeException('LIKE operator not implemented for PostgreSQL dialect.');
	}

	public function ilike(string $left, string $right): string
	{
		throw new RuntimeException('ILIKE operator not implemented for PostgreSQL dialect.');
	}

	public function regex(string $left, string $right): string
	{
		throw new RuntimeException('Regex operator not implemented for PostgreSQL dialect.');
	}

	public function iregex(string $left, string $right): string
	{
		throw new RuntimeException('Regex operator not implemented for PostgreSQL dialect.');
	}

	public function fulltext(string $document, string $query): string
	{
		throw new RuntimeException('Fulltext predicate not implemented for PostgreSQL dialect.');
	}
}
