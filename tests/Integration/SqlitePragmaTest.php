<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\CmsDatabase;
use Duon\Cms\Tests\TestCase;
use Duon\Quma\Connection;
use PDO;

final class SqlitePragmaTest extends TestCase
{
	public function testSqlitePragmasAreApplied(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			$this->markTestSkipped('Unable to create temporary sqlite file');
		}

		try {
			$config = $this->config([
				'db.sqlite.pragmas.foreign_keys' => true,
				'db.sqlite.pragmas.journal_mode' => 'WAL',
				'db.sqlite.pragmas.synchronous' => 'NORMAL',
				'db.sqlite.pragmas.busy_timeout_ms' => 1234,
				'db.sqlite.pragmas.secure_delete' => true,
				'db.sqlite.pragmas.trusted_schema' => false,
			]);
			$dsn = 'sqlite:' . $path;
			$sql = [
				'pgsql' => self::root() . '/db/sql/pgsql',
				'sqlite' => self::root() . '/db/sql/sqlite',
			];
			$migrations = [
				'install' => [
					'pgsql' => self::root() . '/db/migrations/install/pgsql',
					'sqlite' => self::root() . '/db/migrations/install/sqlite',
				],
				'default' => [
					'pgsql' => self::root() . '/db/migrations/update/pgsql',
					'sqlite' => self::root() . '/db/migrations/update/sqlite',
				],
			];
			$conn = new Connection(
				$dsn,
				$sql,
				$migrations,
				fetchMode: PDO::FETCH_ASSOC,
				print: false,
			);
			$db = new CmsDatabase($conn, $config);
			$db->connect();
			$pdo = $db->getConn();

			$this->assertSame(1, (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn());
			$this->assertSame('wal', strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn()));
			$this->assertSame(1, (int) $pdo->query('PRAGMA synchronous')->fetchColumn());
			$this->assertSame(1234, (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn());
			$this->assertSame(1, (int) $pdo->query('PRAGMA secure_delete')->fetchColumn());
			$this->assertSame(0, (int) $pdo->query('PRAGMA trusted_schema')->fetchColumn());
		} finally {
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
}
