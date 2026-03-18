<?php

declare(strict_types=1);

namespace Duon\Cms\Db;

use Duon\Quma\Database;
use RuntimeException;

final class Dialects
{
	public static function for(Database $db): Dialect
	{
		return match ($db->getPdoDriver()) {
			'pgsql' => new PostgresDialect(),
			'sqlite' => new SqliteDialect(),
			default => throw new RuntimeException('Database driver not supported: ' . $db->getPdoDriver()),
		};
	}
}
