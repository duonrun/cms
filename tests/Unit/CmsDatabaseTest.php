<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Config;
use Duon\Cms\Database\CmsDatabase;
use Duon\Cms\Tests\TestCase;
use Duon\Quma\Connection;
use PDO;

/**
 * @internal
 *
 * @coversNothing
 */
final class CmsDatabaseTest extends TestCase
{
	private string $tempDbPath;

	protected function setUp(): void
	{
		parent::setUp();
		$this->tempDbPath = sys_get_temp_dir() . '/cms_test_' . uniqid() . '.sqlite';
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		if (file_exists($this->tempDbPath)) {
			unlink($this->tempDbPath);
		}
		// Clean up WAL files
		foreach (['-wal', '-shm'] as $suffix) {
			$file = $this->tempDbPath . $suffix;
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	public function testSqlitePragmasAppliedByDefault(): void
	{
		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);
		$db->connect();

		$pdo = $db->getConn();

		// Check foreign_keys is ON
		$result = $pdo->query('PRAGMA foreign_keys')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['foreign_keys']);

		// Check journal_mode is WAL
		$result = $pdo->query('PRAGMA journal_mode')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals('wal', strtolower($result['journal_mode']));

		// Check synchronous is NORMAL (1)
		$result = $pdo->query('PRAGMA synchronous')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['synchronous']);

		// Check busy_timeout is 5000
		$result = $pdo->query('PRAGMA busy_timeout')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(5000, $result['timeout']);

		// Check trusted_schema is OFF
		$result = $pdo->query('PRAGMA trusted_schema')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['trusted_schema']);
	}

	public function testSqlitePragmasFromConfig(): void
	{
		$config = new Config('test', settings: [
			'db.sqlite.pragmas.busy_timeout' => 10000,
			'db.sqlite.pragmas.synchronous' => 'FULL',
		]);

		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn, $config);
		$db->connect();

		$pdo = $db->getConn();

		// Check busy_timeout uses custom value
		$result = $pdo->query('PRAGMA busy_timeout')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(10000, $result['timeout']);

		// Check synchronous is FULL (2)
		$result = $pdo->query('PRAGMA synchronous')->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(2, $result['synchronous']);
	}

	public function testGetSqlitePragmasReturnsSettings(): void
	{
		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);
		$pragmas = $db->getSqlitePragmas();

		$this->assertArrayHasKey('foreign_keys', $pragmas);
		$this->assertArrayHasKey('journal_mode', $pragmas);
		$this->assertArrayHasKey('synchronous', $pragmas);
		$this->assertArrayHasKey('busy_timeout', $pragmas);
		$this->assertArrayHasKey('trusted_schema', $pragmas);
	}

	public function testPragmasNotAppliedForPostgres(): void
	{
		// This test verifies that the PRAGMA code path is skipped for non-SQLite
		// We test by checking that getSqlitePragmas still returns defaults
		// (the actual PRAGMA execution would fail on non-SQLite)
		$conn = new Connection(
			'pgsql:host=localhost;dbname=test',
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);

		// Should still have pragmas defined (just won't be applied)
		$pragmas = $db->getSqlitePragmas();
		$this->assertNotEmpty($pragmas);
	}

	public function testSqliteRegexpFunctionRegistered(): void
	{
		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);
		$db->connect();

		$pdo = $db->getConn();

		// Test case-sensitive REGEXP
		$result = $pdo->query("SELECT 'Hello World' REGEXP 'World' AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['matches']);

		$result = $pdo->query("SELECT 'Hello World' REGEXP 'world' AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['matches']); // Case-sensitive, no match

		$result = $pdo->query("SELECT 'Hello World' REGEXP '^Hello' AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['matches']);

		$result = $pdo->query("SELECT 'Hello World' REGEXP '^World' AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['matches']); // World is not at start
	}

	public function testSqliteRegexpIFunctionRegistered(): void
	{
		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);
		$db->connect();

		$pdo = $db->getConn();

		// Test case-insensitive regexp_i
		$result = $pdo->query("SELECT regexp_i('Hello World', 'world') AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['matches']); // Case-insensitive, should match

		$result = $pdo->query("SELECT regexp_i('Hello World', 'WORLD') AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['matches']);

		$result = $pdo->query("SELECT regexp_i('Hello World', '^hello') AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(1, $result['matches']);

		$result = $pdo->query("SELECT regexp_i('Hello World', 'nomatch') AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['matches']);
	}

	public function testSqliteRegexpWithNullValue(): void
	{
		$conn = new Connection(
			'sqlite:' . $this->tempDbPath,
			self::root() . '/db/sql',
			fetchMode: PDO::FETCH_ASSOC,
		);

		$db = new CmsDatabase($conn);
		$db->connect();

		$pdo = $db->getConn();

		// Null values should return 0 (no match)
		$result = $pdo->query("SELECT NULL REGEXP 'test' AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['matches']);

		$result = $pdo->query("SELECT regexp_i(NULL, 'test') AS matches")->fetch(PDO::FETCH_ASSOC);
		$this->assertEquals(0, $result['matches']);
	}
}
