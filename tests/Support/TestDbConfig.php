<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Support;

use Duon\Quma\Connection;
use PDO;
use RuntimeException;

final class TestDbConfig
{
	private const DEFAULT_PGSQL_DSN = 'pgsql:host=localhost;dbname=duoncms;user=duoncms;password=duoncms';
	private const DEFAULT_SQLITE_FILE = 'duon-cms-test.sqlite';
	private static ?string $sqlitePathOverride = null;

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

	public static function sanitizedDsn(): string
	{
		$dsn = self::dsn();
		$masked = preg_replace('/(password|pass)=([^;]+)/i', '$1=***', $dsn);

		return $masked ?? $dsn;
	}

	public static function sqlitePath(): string
	{
		if (self::$sqlitePathOverride !== null) {
			return self::$sqlitePathOverride;
		}

		$path = getenv('CMS_TEST_SQLITE_PATH');

		if ($path !== false && $path !== '') {
			return $path;
		}

		$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

		return $directory . DIRECTORY_SEPARATOR . self::DEFAULT_SQLITE_FILE;
	}

	public static function initSqliteFile(): string
	{
		$envPath = getenv('CMS_TEST_SQLITE_PATH');

		if ($envPath !== false && $envPath !== '') {
			self::$sqlitePathOverride = $envPath;

			return $envPath;
		}

		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			throw new RuntimeException('Unable to create temporary sqlite file');
		}

		self::$sqlitePathOverride = $path;

		register_shutdown_function(static function () use ($path): void {
			self::cleanupSqliteFile($path);
		});

		return $path;
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

	public static function migrationDirsForDriver(string $namespace): array
	{
		$migrations = self::migrationDirs();
		$dirs = $migrations[$namespace] ?? [];

		if (is_string($dirs)) {
			return [$dirs];
		}

		if (is_array($dirs) && array_key_exists(self::driver(), $dirs)) {
			$entry = $dirs[self::driver()];

			return is_array($entry) ? $entry : [$entry];
		}

		return [];
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

	private static function cleanupSqliteFile(string $path): void
	{
		$walPath = $path . '-wal';
		$shmPath = $path . '-shm';

		if (is_file($walPath)) {
			unlink($walPath);
		}
		if (is_file($shmPath)) {
			unlink($shmPath);
		}
		if (is_file($path)) {
			unlink($path);
		}
	}
}
