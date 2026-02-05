<?php

declare(strict_types=1);

namespace Duon\Cms\Database;

use Duon\Cms\Config;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use PDO;

/**
 * CMS Database wrapper that applies driver-specific initialization.
 *
 * For SQLite: applies recommended PRAGMAs for production-safe defaults.
 */
class CmsDatabase extends Database
{
	/** @var array<string, scalar> */
	private array $sqlitePragmas;

	private bool $initialized = false;

	public function __construct(
		Connection $conn,
		?Config $config = null,
	) {
		parent::__construct($conn);
		$this->sqlitePragmas = $this->loadSqlitePragmas($config);
	}

	public function connect(): static
	{
		parent::connect();

		if (!$this->initialized) {
			$this->initialized = true;
			$this->applyDriverInit();
		}

		return $this;
	}

	/**
	 * Load SQLite PRAGMA settings from config with sensible defaults.
	 *
	 * @return array<string, scalar>
	 */
	private function loadSqlitePragmas(?Config $config): array
	{
		$defaults = [
			'foreign_keys' => 1,
			'journal_mode' => 'WAL',
			'synchronous' => 'NORMAL',
			'busy_timeout' => 5000,
			'trusted_schema' => 0,
		];

		if ($config === null) {
			return $defaults;
		}

		return [
			'foreign_keys' => $config->get('db.sqlite.pragmas.foreign_keys', $defaults['foreign_keys']),
			'journal_mode' => $config->get('db.sqlite.pragmas.journal_mode', $defaults['journal_mode']),
			'synchronous' => $config->get('db.sqlite.pragmas.synchronous', $defaults['synchronous']),
			'busy_timeout' => $config->get('db.sqlite.pragmas.busy_timeout', $defaults['busy_timeout']),
			'trusted_schema' => $config->get('db.sqlite.pragmas.trusted_schema', $defaults['trusted_schema']),
		];
	}

	/**
	 * Apply driver-specific initialization after connection.
	 */
	private function applyDriverInit(): void
	{
		if ($this->getPdoDriver() !== 'sqlite') {
			return;
		}

		$pdo = $this->getConn();

		foreach ($this->sqlitePragmas as $pragma => $value) {
			// Use exec for PRAGMAs as they don't need prepared statements
			$pdo->exec("PRAGMA {$pragma} = {$value}");
		}

		$this->registerSqliteFunctions($pdo);
	}

	/**
	 * Register custom SQLite functions for regex support.
	 *
	 * Uses PDO::sqliteCreateFunction() which is deprecated in PHP 8.5+
	 * but still works. The Quma library creates PDO instances with `new PDO()`
	 * which doesn't return the typed Pdo\Sqlite subclass, so we must use
	 * the deprecated method. Suppressed via @ operator.
	 */
	private function registerSqliteFunctions(PDO $pdo): void
	{
		// Case-sensitive regex: column REGEXP pattern
		// SQLite's REGEXP operator calls a user-defined function with (pattern, value) order
		@$pdo->sqliteCreateFunction(
			'REGEXP',
			static function (string $pattern, ?string $value): int {
				if ($value === null) {
					return 0;
				}

				return preg_match('/' . $pattern . '/', $value) === 1 ? 1 : 0;
			},
			2,
		);

		// Case-insensitive regex: regexp_i(value, pattern)
		// Custom function with (value, pattern) order for consistency with dialect output
		@$pdo->sqliteCreateFunction(
			'regexp_i',
			static function (?string $value, string $pattern): int {
				if ($value === null) {
					return 0;
				}

				return preg_match('/' . $pattern . '/i', $value) === 1 ? 1 : 0;
			},
			2,
		);
	}

	/**
	 * Get the current SQLite PRAGMA settings.
	 *
	 * @return array<string, scalar>
	 */
	public function getSqlitePragmas(): array
	{
		return $this->sqlitePragmas;
	}
}
