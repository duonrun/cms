<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\CmsDatabase;
use Duon\Cms\Tests\Support\TestDbConfig;
use Duon\Cms\Tests\TestCase;
use Duon\Quma\Connection;
use PDO;

final class SqliteRegexTest extends TestCase
{
	public function testSqliteRegexFunctionsAreRegistered(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			$this->markTestSkipped('Unable to create temporary sqlite file');
		}

		try {
			$config = $this->config();
			$dsn = 'sqlite:' . $path;
			$sql = TestDbConfig::sqlDirs();
			$migrations = TestDbConfig::migrationDirs();
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

			$this->assertSame(1, (int) $pdo->query("SELECT 'Test' REGEXP '^T'")->fetchColumn());
			$this->assertSame(0, (int) $pdo->query("SELECT 'test' REGEXP '^T'")->fetchColumn());
			$this->assertSame(1, (int) $pdo->query("SELECT regexp_i('test', '^T')")->fetchColumn());
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
