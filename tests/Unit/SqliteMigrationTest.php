<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Tests\TestCase;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use PDO;
use PDOException;

/**
 * Smoke test for SQLite migrations.
 *
 * This test verifies that the SQLite DDL migrations can be applied
 * successfully and produce a working schema.
 *
 * @internal
 *
 * @coversNothing
 */
final class SqliteMigrationTest extends TestCase
{
	private ?string $dbPath = null;
	private ?Database $db = null;

	protected function setUp(): void
	{
		parent::setUp();

		// Create a temporary SQLite database file
		$this->dbPath = sys_get_temp_dir() . '/duon_cms_test_' . uniqid() . '.sqlite';
	}

	protected function tearDown(): void
	{
		$this->db = null;

		// Clean up temporary database file
		if ($this->dbPath && file_exists($this->dbPath)) {
			unlink($this->dbPath);
		}

		// Clean up WAL and SHM files if they exist
		if ($this->dbPath) {
			$walFile = $this->dbPath . '-wal';
			$shmFile = $this->dbPath . '-shm';

			if (file_exists($walFile)) {
				unlink($walFile);
			}

			if (file_exists($shmFile)) {
				unlink($shmFile);
			}
		}

		parent::tearDown();
	}

	private function createDatabase(): Database
	{
		$connection = new Connection(
			'sqlite:' . $this->dbPath,
			[
				'pgsql' => self::root() . '/db/sql/pgsql',
				'sqlite' => self::root() . '/db/sql/sqlite',
			],
			[
				'install' => [[
					'pgsql' => self::root() . '/db/migrations/install/pgsql',
					'sqlite' => self::root() . '/db/migrations/install/sqlite',
				]],
				'default' => [[
					'pgsql' => self::root() . '/db/migrations/update/pgsql',
					'sqlite' => self::root() . '/db/migrations/update/sqlite',
				]],
			],
			fetchMode: PDO::FETCH_ASSOC,
			print: false,
		);

		return new Database($connection);
	}

	public function testSqliteDdlMigrationCanBeApplied(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		// Enable foreign keys
		$pdo->exec('PRAGMA foreign_keys = ON');

		// Read and execute the DDL migration
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$ddlSql = file_get_contents($ddlPath);

		// Split by semicolons but handle triggers (which contain semicolons)
		// Execute the entire script as one transaction
		$pdo->exec($ddlSql);

		// Verify core tables exist
		$tables = $this->getTableNames($pdo);

		$expectedTables = [
			'cms_userroles',
			'cms_users',
			'cms_authtokens',
			'cms_onetimetokens',
			'cms_loginsessions',
			'cms_types',
			'cms_nodes',
			'cms_fulltext',
			'cms_urlpaths',
			'cms_drafts',
			'cms_menus',
			'cms_menuitems',
			'cms_topics',
			'cms_tags',
			'cms_nodetags',
			'audit_nodes',
			'audit_drafts',
			'audit_users',
		];

		foreach ($expectedTables as $table) {
			$this->assertContains($table, $tables, "Table '{$table}' should exist");
		}
	}

	public function testSqliteSeedDataCanBeApplied(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		// Enable foreign keys
		$pdo->exec('PRAGMA foreign_keys = ON');

		// Apply DDL first
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$pdo->exec(file_get_contents($ddlPath));

		// Create migrations table (normally done by Quma)
		$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
			migration TEXT PRIMARY KEY,
			applied TEXT NOT NULL
		)');

		// Apply seed data
		$dataPath = self::root() . '/db/migrations/install/sqlite/000000-000001-init-data.sql';
		$pdo->exec(file_get_contents($dataPath));

		// Verify user roles exist
		$stmt = $pdo->query('SELECT userrole FROM cms_userroles ORDER BY userrole');
		$roles = array_column($stmt->fetchAll(), 'userrole');
		$this->assertEquals(['admin', 'editor', 'superuser', 'system'], $roles);

		// Verify system user exists
		$stmt = $pdo->query("SELECT username, email, userrole FROM cms_users WHERE uid = '0000000000000'");
		$user = $stmt->fetch();
		$this->assertNotFalse($user);
		$this->assertEquals('system', $user['username']);
		$this->assertEquals('system@duon.dev', $user['email']);
		$this->assertEquals('system', $user['userrole']);
	}

	public function testJsonColumnsEnforceValidJson(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		$pdo->exec('PRAGMA foreign_keys = ON');
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$pdo->exec(file_get_contents($ddlPath));

		// Insert valid user roles first
		$pdo->exec("INSERT INTO cms_userroles (userrole) VALUES ('editor')");

		// Try to insert a user with invalid JSON - should fail
		$this->expectException(PDOException::class);
		$pdo->exec("INSERT INTO cms_users (uid, username, pwhash, userrole, active, data, creator, editor)
			VALUES ('test', 'test', 'hash', 'editor', 1, 'not valid json', 1, 1)");
	}

	public function testForeignKeyConstraintsAreEnforced(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		$pdo->exec('PRAGMA foreign_keys = ON');
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$pdo->exec(file_get_contents($ddlPath));

		// Try to insert a user with non-existent userrole - should fail
		$this->expectException(PDOException::class);
		$this->expectExceptionMessage('FOREIGN KEY constraint failed');
		$pdo->exec("INSERT INTO cms_users (uid, username, pwhash, userrole, active, data, creator, editor)
			VALUES ('test', 'test', 'hash', 'nonexistent', 1, '{}', 1, 1)");
	}

	public function testBooleanColumnsUseIntegerValues(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		$pdo->exec('PRAGMA foreign_keys = ON');
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$pdo->exec(file_get_contents($ddlPath));
		$dataPath = self::root() . '/db/migrations/install/sqlite/000000-000001-init-data.sql';
		$pdo->exec('CREATE TABLE migrations (migration TEXT PRIMARY KEY, applied TEXT NOT NULL)');
		$pdo->exec(file_get_contents($dataPath));

		// Insert a type for testing
		$pdo->exec("INSERT INTO cms_types (handle, kind) VALUES ('test-page', 'page')");
		$typeId = $pdo->lastInsertId();

		// Insert a node with boolean values
		$pdo->exec("INSERT INTO cms_nodes (uid, published, hidden, locked, type, creator, editor, content)
			VALUES ('test-node', 1, 0, 0, {$typeId}, 1, 1, '{}')");

		// Verify boolean values are stored as integers
		$stmt = $pdo->query("SELECT published, hidden, locked FROM cms_nodes WHERE uid = 'test-node'");
		$node = $stmt->fetch();
		$this->assertSame(1, (int) $node['published']);
		$this->assertSame(0, (int) $node['hidden']);
		$this->assertSame(0, (int) $node['locked']);
	}

	public function testUpdateMigrationsCanBeApplied(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		$pdo->exec('PRAGMA foreign_keys = ON');

		// Apply install migrations
		$ddlPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
		$pdo->exec(file_get_contents($ddlPath));

		$pdo->exec('CREATE TABLE migrations (migration TEXT PRIMARY KEY, applied TEXT NOT NULL)');
		$dataPath = self::root() . '/db/migrations/install/sqlite/000000-000001-init-data.sql';
		$pdo->exec(file_get_contents($dataPath));

		// Apply update migrations (these are placeholders for SQLite but should run without error)
		$updateDir = self::root() . '/db/migrations/update/sqlite';
		$files = glob($updateDir . '/*.sql');
		$this->assertNotEmpty($files, 'Update migrations should exist for SQLite');

		foreach ($files as $file) {
			$sql = file_get_contents($file);
			$this->assertNotFalse($sql);
			// Execute the migration - should not throw
			$pdo->exec($sql);
		}

		// Verify database is still functional after update migrations
		$stmt = $pdo->query('SELECT COUNT(*) as cnt FROM cms_userroles');
		$result = $stmt->fetch();
		$this->assertGreaterThan(0, (int) $result['cnt']);
	}

	/**
	 * @return string[]
	 */
	private function getTableNames(PDO $pdo): array
	{
		$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");

		return array_column($stmt->fetchAll(), 'name');
	}

	/**
	 * Performance regression guard: verify hot queries use indexes.
	 *
	 * This test ensures that commonly-executed queries use SEARCH (indexed)
	 * rather than SCAN (full table scan) operations.
	 */
	public function testHotQueriesUseIndexes(): void
	{
		$this->db = $this->createDatabase();
		$pdo = $this->db->getConn();

		// Apply migrations
		$pdo->exec('PRAGMA foreign_keys = ON');
		$pdo->exec(file_get_contents(self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql'));
		$pdo->exec('CREATE TABLE migrations (migration TEXT PRIMARY KEY, applied TEXT NOT NULL)');
		$pdo->exec(file_get_contents(self::root() . '/db/migrations/install/sqlite/000000-000001-init-data.sql'));

		foreach (glob(self::root() . '/db/migrations/update/sqlite/*.sql') as $file) {
			$pdo->exec(file_get_contents($file));
		}

		// Test urlpaths lookup by path - should use ux_urlpaths_path
		$plan = $this->explainQueryPlan($pdo, "SELECT * FROM cms_urlpaths WHERE path = '/test'");
		$this->assertStringContainsString('SEARCH', $plan, 'byPath query should use an index');
		$this->assertStringContainsString('ux_urlpaths_path', $plan, 'byPath query should use ux_urlpaths_path index');

		// Test nodes lookup by type - should use ix_nodes_type
		$plan = $this->explainQueryPlan($pdo, 'SELECT * FROM cms_nodes WHERE type = 1 AND deleted IS NULL');
		$this->assertStringContainsString('SEARCH', $plan, 'nodes by type query should use an index');

		// Test nodetags lookup by tag - should use ix_nodetags_tag
		$plan = $this->explainQueryPlan($pdo, 'SELECT * FROM cms_nodetags WHERE tag = 1');
		$this->assertStringContainsString('SEARCH', $plan, 'nodetags by tag query should use an index');
		$this->assertStringContainsString('ix_nodetags_tag', $plan, 'nodetags query should use ix_nodetags_tag index');

		// Test fulltext search - should use FTS5 virtual table
		$plan = $this->explainQueryPlan($pdo, "
			SELECT idx.node FROM cms_fulltext_idx idx
			JOIN cms_fulltext_fts fts ON idx.rowid = fts.rowid
			WHERE cms_fulltext_fts MATCH 'test'
		");
		$this->assertStringContainsString('VIRTUAL TABLE', $plan, 'fulltext search should use FTS5 virtual table');
	}

	/**
	 * Run EXPLAIN QUERY PLAN and return the output as a string.
	 */
	private function explainQueryPlan(PDO $pdo, string $sql): string
	{
		$stmt = $pdo->query('EXPLAIN QUERY PLAN ' . $sql);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$output = [];

		foreach ($rows as $row) {
			$output[] = $row['detail'] ?? json_encode($row);
		}

		return implode("\n", $output);
	}
}
