<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Support;

use Duon\Quma\Connection;
use PDO;

final class TestDbConfig
{
	private const DEFAULT_PGSQL_DSN = 'pgsql:host=localhost;dbname=duoncms;user=duoncms;password=duoncms';
	private const DEFAULT_SQLITE_FILE = 'duon-cms-test.sqlite';

	public static function driver(): string
	{
		$driver = getenv('CMS_TEST_DRIVER') ?: 'pgsql';

		return in_array($driver, ['pgsql', 'sqlite'], true) ? $driver : 'pgsql';
	}

	public static function dsn(): string
	{
		if (self::driver() === 'sqlite') {
			return 'sqlite:' . self::sqlitePath();
		}

		$dsn = getenv('CMS_TEST_PGSQL_DSN');

		return $dsn !== false && $dsn !== '' ? $dsn : self::DEFAULT_PGSQL_DSN;
	}

	public static function sqlitePath(): string
	{
		$path = getenv('CMS_TEST_SQLITE_PATH');

		if ($path !== false && $path !== '') {
			return $path;
		}

		$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

		return $directory . DIRECTORY_SEPARATOR . self::DEFAULT_SQLITE_FILE;
	}

	public static function sqlDirs(): array
	{
		$root = self::root();

		return [
			'pgsql' => $root . '/db/sql/pgsql',
			'sqlite' => $root . '/db/sql/sqlite',
		];
	}

	public static function migrationDirs(): array
	{
		$root = self::root();

		return [
			'install' => [
				'pgsql' => $root . '/db/migrations/install/pgsql',
				'sqlite' => $root . '/db/migrations/install/sqlite',
			],
			'default' => [
				'pgsql' => $root . '/db/migrations/update/pgsql',
				'sqlite' => $root . '/db/migrations/update/sqlite',
			],
		];
	}

	public static function options(): array
	{
		return [];
	}

	public static function fetchMode(): int
	{
		return PDO::FETCH_ASSOC;
	}

	public static function print(): bool
	{
		return false;
	}

	public static function cmsConfigOverrides(): array
	{
		return [
			'db.dsn' => self::dsn(),
			'db.options' => self::options(),
			'db.print' => self::print(),
		];
	}

	public static function connection(): Connection
	{
		return new Connection(
			self::dsn(),
			self::sqlDirs(),
			self::migrationDirs(),
			fetchMode: self::fetchMode(),
			options: self::options(),
			print: self::print(),
		);
	}

	private static function root(): string
	{
		return dirname(__DIR__, 2);
	}
}
