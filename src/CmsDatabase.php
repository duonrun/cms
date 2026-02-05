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
		}

		return $this;
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
}
