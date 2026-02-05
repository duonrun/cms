<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Support;

use InvalidArgumentException;
use PDO;

/**
 * Centralized test database configuration.
 *
 * Reads CMS_TEST_DRIVER environment variable to select between SQLite and PostgreSQL.
 * Provides DSN, connection options, and path configurations for all test cases.
 *
 * Usage:
 *   CMS_TEST_DRIVER=sqlite ./vendor/bin/phpunit   # Run tests with SQLite
 *   CMS_TEST_DRIVER=pgsql ./vendor/bin/phpunit    # Run tests with PostgreSQL
 *
 * Default: pgsql (Phase A) - will change to sqlite after parity is reached
 */
final class TestDbConfig
{
	public const DRIVER_PGSQL = 'pgsql';
	public const DRIVER_SQLITE = 'sqlite';
	public const DEFAULT_DRIVER = self::DRIVER_PGSQL;

	private static ?self $instance = null;
	private string $driver;
	private string $dsn;
	private ?string $sqliteDbPath = null;

	private function __construct()
	{
		$this->driver = $this->resolveDriver();
		$this->dsn = $this->resolveDsn();
	}

	public static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Reset the singleton (useful for testing the config itself).
	 */
	public static function reset(): void
	{
		self::$instance = null;
	}

	/**
	 * Get the configured driver name ('pgsql' or 'sqlite').
	 */
	public function driver(): string
	{
		return $this->driver;
	}

	/**
	 * Get the DSN for the test database.
	 */
	public function dsn(): string
	{
		return $this->dsn;
	}

	/**
	 * Check if the current driver is SQLite.
	 */
	public function isSqlite(): bool
	{
		return $this->driver === self::DRIVER_SQLITE;
	}

	/**
	 * Check if the current driver is PostgreSQL.
	 */
	public function isPgsql(): bool
	{
		return $this->driver === self::DRIVER_PGSQL;
	}

	/**
	 * Get the SQLite database file path (null for PostgreSQL).
	 */
	public function sqliteDbPath(): ?string
	{
		return $this->sqliteDbPath;
	}

	/**
	 * Get driver-aware SQL directories for Quma.
	 *
	 * @return array<string, string>
	 */
	public function sqlDirs(string $root): array
	{
		return [
			'pgsql' => $root . '/db/sql/pgsql',
			'sqlite' => $root . '/db/sql/sqlite',
		];
	}

	/**
	 * Get driver-aware migration directories for Quma.
	 *
	 * @return array<string, array<array<string, string>>>
	 */
	public function migrationDirs(string $root): array
	{
		return [
			'install' => [[
				'pgsql' => $root . '/db/migrations/install/pgsql',
				'sqlite' => $root . '/db/migrations/install/sqlite',
			]],
			'default' => [[
				'pgsql' => $root . '/db/migrations/update/pgsql',
				'sqlite' => $root . '/db/migrations/update/sqlite',
			]],
		];
	}

	/**
	 * Get the default PDO fetch mode.
	 */
	public function fetchMode(): int
	{
		return PDO::FETCH_ASSOC;
	}

	/**
	 * Resolve the driver from environment variable.
	 */
	private function resolveDriver(): string
	{
		$driver = getenv('CMS_TEST_DRIVER');

		if ($driver === false || $driver === '') {
			return self::DEFAULT_DRIVER;
		}

		$driver = strtolower($driver);

		if (!in_array($driver, [self::DRIVER_PGSQL, self::DRIVER_SQLITE], true)) {
			throw new InvalidArgumentException(
				"Invalid CMS_TEST_DRIVER: '{$driver}'. Valid values: pgsql, sqlite",
			);
		}

		return $driver;
	}

	/**
	 * Resolve the DSN based on driver.
	 */
	private function resolveDsn(): string
	{
		if ($this->driver === self::DRIVER_SQLITE) {
			return $this->resolveSqliteDsn();
		}

		return $this->resolvePgsqlDsn();
	}

	/**
	 * Resolve PostgreSQL DSN from environment or use defaults.
	 */
	private function resolvePgsqlDsn(): string
	{
		$dsn = getenv('CMS_TEST_PGSQL_DSN');

		if ($dsn !== false && $dsn !== '') {
			return $dsn;
		}

		// Default PostgreSQL test database
		$host = getenv('CMS_TEST_PGSQL_HOST') ?: 'localhost';
		$port = getenv('CMS_TEST_PGSQL_PORT') ?: '5432';
		$dbname = getenv('CMS_TEST_PGSQL_DBNAME') ?: 'duoncms';
		$user = getenv('CMS_TEST_PGSQL_USER') ?: 'duoncms';
		$password = getenv('CMS_TEST_PGSQL_PASSWORD') ?: 'duoncms';

		return "pgsql:host={$host};port={$port};dbname={$dbname};user={$user};password={$password}";
	}

	/**
	 * Resolve SQLite DSN, creating a temp file for integration/e2e tests.
	 */
	private function resolveSqliteDsn(): string
	{
		$dsn = getenv('CMS_TEST_SQLITE_DSN');

		if ($dsn !== false && $dsn !== '') {
			// Extract path from DSN if it's a file DSN
			if (str_starts_with($dsn, 'sqlite:') && !str_contains($dsn, ':memory:')) {
				$this->sqliteDbPath = substr($dsn, 7);
			}

			return $dsn;
		}

		// Create a unique temp file for this test run
		// Using a consistent name per run allows E2E tests to share the database
		$runId = getenv('CMS_TEST_RUN_ID') ?: getmypid();
		$this->sqliteDbPath = sys_get_temp_dir() . "/cms_test_{$runId}.sqlite";

		return 'sqlite:' . $this->sqliteDbPath;
	}

	/**
	 * Clean up the SQLite test database file.
	 *
	 * Call this after all tests complete to remove the temp file.
	 */
	public function cleanup(): void
	{
		if ($this->sqliteDbPath === null) {
			return;
		}

		foreach (['', '-wal', '-shm'] as $suffix) {
			$file = $this->sqliteDbPath . $suffix;

			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * Get a human-readable description of the current configuration.
	 */
	public function describe(): string
	{
		$driver = strtoupper($this->driver);

		if ($this->driver === self::DRIVER_SQLITE) {
			$path = $this->sqliteDbPath ?? ':memory:';

			return "{$driver} - {$path}";
		}

		// Sanitize DSN (hide password)
		$sanitized = preg_replace('/password=[^;]+/', 'password=***', $this->dsn);

		return "{$driver} - {$sanitized}";
	}
}
