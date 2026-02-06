<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

use Duon\Cms\Exception\RuntimeException;

final class SqlDialectFactory
{
	public static function fromDriver(string $driver): SqlDialect
	{
		return match ($driver) {
			'pgsql' => new PostgresDialect(),
			'sqlite' => new SqliteDialect(),
			default => throw new RuntimeException('Unsupported PDO driver for Finder dialect: ' . $driver),
		};
	}
}
