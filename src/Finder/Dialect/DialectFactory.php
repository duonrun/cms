<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

use Duon\Cms\Exception\RuntimeException;

/**
 * Factory for creating SQL dialect instances based on PDO driver.
 */
final class DialectFactory
{
	private static ?SqlDialect $instance = null;
	private static ?string $currentDriver = null;

	/**
	 * Get the dialect for the given PDO driver.
	 *
	 * @param string $driver PDO driver name ('pgsql', 'sqlite')
	 * @throws RuntimeException If the driver is not supported
	 */
	public static function forDriver(string $driver): SqlDialect
	{
		// Cache the dialect instance for repeated calls with same driver
		if (self::$currentDriver === $driver && self::$instance !== null) {
			return self::$instance;
		}

		self::$currentDriver = $driver;
		self::$instance = match ($driver) {
			'pgsql' => new PostgresDialect(),
			'sqlite' => new SqliteDialect(),
			default => throw new RuntimeException(
				"Unsupported database driver for Finder: '{$driver}'. "
				. 'Supported drivers: pgsql, sqlite',
			),
		};

		return self::$instance;
	}

	/**
	 * Reset the cached dialect instance.
	 *
	 * Primarily for testing purposes.
	 */
	public static function reset(): void
	{
		self::$instance = null;
		self::$currentDriver = null;
	}
}
