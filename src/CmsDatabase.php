<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Quma\Connection;
use Duon\Quma\Database;

final class CmsDatabase extends Database
{
	public function __construct(Connection $conn, private readonly Config $config)
	{
		parent::__construct($conn);
	}

	public function connect(): static
	{
		$wasConnected = isset($this->pdo);

		parent::connect();

		if (!$wasConnected) {
			$this->applySqlitePragmas();
			$this->registerSqliteFunctions();
		}

		return $this;
	}

	public function fulltextEnabled(): bool
	{
		return $this->config->fulltextEnabled($this->getPdoDriver());
	}

	private function applySqlitePragmas(): void
	{
		if ($this->getPdoDriver() !== 'sqlite') {
			return;
		}

		$foreignKeys = $this->config->get('db.sqlite.pragmas.foreign_keys', true) ? 'ON' : 'OFF';
		$journalMode = strtoupper((string) $this->config->get('db.sqlite.pragmas.journal_mode', 'WAL'));
		$synchronous = strtoupper((string) $this->config->get('db.sqlite.pragmas.synchronous', 'NORMAL'));
		$busyTimeout = (int) $this->config->get('db.sqlite.pragmas.busy_timeout_ms', 5000);
		$secureDelete = $this->config->get('db.sqlite.pragmas.secure_delete', false) ? 'ON' : 'OFF';
		$trustedSchema = $this->config->get('db.sqlite.pragmas.trusted_schema', false) ? 'ON' : 'OFF';

		$pdo = $this->getConn();
		$pdo->exec('PRAGMA foreign_keys = ' . $foreignKeys);
		$pdo->exec('PRAGMA journal_mode = ' . $journalMode);
		$pdo->exec('PRAGMA synchronous = ' . $synchronous);
		$pdo->exec('PRAGMA busy_timeout = ' . $busyTimeout);
		$pdo->exec('PRAGMA secure_delete = ' . $secureDelete);
		$pdo->exec('PRAGMA trusted_schema = ' . $trustedSchema);
	}

	private function registerSqliteFunctions(): void
	{
		if ($this->getPdoDriver() !== 'sqlite') {
			return;
		}

		$pdo = $this->getConn();
		$pdo->sqliteCreateFunction(
			'regexp',
			fn(?string $pattern, mixed $value): int => $this->matchRegex($pattern, $value, false),
			2,
		);
		$pdo->sqliteCreateFunction(
			'regexp_i',
			fn(mixed $value, ?string $pattern): int => $this->matchRegex($pattern, $value, true),
			2,
		);
	}

	private function matchRegex(?string $pattern, mixed $value, bool $ignoreCase): int
	{
		if ($pattern === null || $value === null) {
			return 0;
		}

		$regex = $this->buildRegex($pattern, $ignoreCase);

		return preg_match($regex, (string) $value) === 1 ? 1 : 0;
	}

	private function buildRegex(string $pattern, bool $ignoreCase): string
	{
		$delimiter = '~';
		$escaped = str_replace($delimiter, '\\' . $delimiter, $pattern);
		$flags = $ignoreCase ? 'i' : '';

		return $delimiter . $escaped . $delimiter . $flags;
	}
}
