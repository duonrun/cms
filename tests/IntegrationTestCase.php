<?php

declare(strict_types=1);

namespace Duon\Cms\Tests;

use Duon\Cms\Context;
use Duon\Cms\Database\CmsDatabase;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Tests\Support\TestDbConfig;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use Duon\Registry\Registry;
use RuntimeException;

/**
 * Base class for integration tests that interact with the database.
 *
 * This class extends TestCase and enables transaction-based test isolation
 * by default, ensuring each test has a clean database state.
 *
 * @internal
 *
 * @coversNothing
 */
class IntegrationTestCase extends TestCase
{
	protected static bool $dbInitialized = false;
	protected static ?Connection $sharedConnection = null;
	protected ?Database $testDb = null;
	protected bool $useTransactions = true;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		if (!self::$dbInitialized) {
			self::initializeTestDatabase();
			self::$dbInitialized = true;
		}
	}

	protected static function initializeTestDatabase(): void
	{
		$config = self::testDbConfig();

		// Create shared connection for migration check
		self::$sharedConnection = new Connection(
			$config->dsn(),
			$config->sqlDirs(self::root()),
			$config->migrationDirs(self::root()),
			fetchMode: $config->fetchMode(),
			print: false,
		);

		$db = new CmsDatabase(self::$sharedConnection);
		$db->connect();

		if ($config->isSqlite()) {
			self::initializeSqliteDatabase($db);
		} else {
			self::initializePostgresDatabase($db);
		}
	}

	/**
	 * Initialize SQLite test database by applying migrations if needed.
	 */
	protected static function initializeSqliteDatabase(Database $db): void
	{
		// Check if the cms_nodes table exists (indicates migrations have been run)
		$result = $db->execute(
			"SELECT name FROM sqlite_master WHERE type='table' AND name='cms_nodes'",
		)->one();

		if ($result === null) {
			// Apply migrations directly by executing SQL files
			self::applySqliteMigrations($db);
		}
	}

	/**
	 * Apply SQLite migrations by executing SQL files directly.
	 *
	 * Note: This is a simplified migration runner for tests. It doesn't
	 * use Quma's migration tracking (migrations table). This is fine for
	 * tests because we create a fresh database for each test run.
	 *
	 * Uses PDO::exec() instead of prepared statements because SQLite
	 * with prepared statements only executes the first statement in
	 * multi-statement SQL files.
	 */
	protected static function applySqliteMigrations(Database $db): void
	{
		$root = self::root();
		$installDir = $root . '/db/migrations/install/sqlite';
		$updateDir = $root . '/db/migrations/update/sqlite';

		// Get the PDO connection for direct exec()
		$pdo = $db->getConn();

		// Apply install migrations in order
		$installFiles = glob($installDir . '/*.sql') ?: [];
		sort($installFiles);

		foreach ($installFiles as $file) {
			$sql = file_get_contents($file);

			if ($sql !== false && trim($sql) !== '') {
				// Use exec() for multi-statement DDL files
				$pdo->exec($sql);
			}
		}

		// Apply update migrations in order
		$updateFiles = glob($updateDir . '/*.sql') ?: [];
		sort($updateFiles);

		foreach ($updateFiles as $file) {
			$sql = file_get_contents($file);

			if ($sql !== false && trim($sql) !== '') {
				$pdo->exec($sql);
			}
		}
	}

	/**
	 * Initialize PostgreSQL test database, checking for required schema.
	 */
	protected static function initializePostgresDatabase(Database $db): void
	{
		// Check if migrations table exists
		$tableExists = $db->execute(
			"SELECT EXISTS (
				SELECT FROM information_schema.tables
				WHERE table_schema = 'public'
				AND table_name = 'migrations'
			) as exists",
		)->one()['exists'] ?? false;

		if (!$tableExists) {
			echo "\n⚠ Test database not initialized. Run: ./run recreate-db && ./run migrate --apply\n\n";

			throw new RuntimeException(
				'Test database not initialized. Run: ./run recreate-db && ./run migrate --apply',
			);
		}

		// Check if cms schema exists (indicates migrations have been run)
		$schemaExists = $db->execute(
			"SELECT EXISTS (
				SELECT FROM information_schema.schemata
				WHERE schema_name = 'cms'
			) as exists",
		)->one()['exists'] ?? false;

		if (!$schemaExists) {
			echo "\n⚠ Migrations not applied. Run: ./run migrate --apply\n\n";

			throw new RuntimeException(
				'Migrations not applied to test database. Run: ./run migrate --apply',
			);
		}
	}

	protected function setUp(): void
	{
		parent::setUp();

		// Begin transaction if this test uses them
		if ($this->useTransactions) {
			$this->testDb = new CmsDatabase($this->conn());
			$this->testDb->connect();
			$this->testDb->begin();
		}
	}

	protected function tearDown(): void
	{
		// Rollback transaction if this test used them
		if ($this->useTransactions && $this->testDb !== null) {
			$this->testDb->rollback();
			$this->testDb = null;
		}

		parent::tearDown();
	}

	public function conn(): Connection
	{
		$config = self::testDbConfig();

		return new Connection(
			$config->dsn(),
			$config->sqlDirs(self::root()),
			$config->migrationDirs(self::root()),
			fetchMode: $config->fetchMode(),
			print: false,
		);
	}

	public function db(): Database
	{
		// If using transactions, return the same database instance
		if ($this->useTransactions && $this->testDb !== null) {
			return $this->testDb;
		}

		$db = new CmsDatabase($this->conn());
		$db->connect();

		return $db;
	}

	public function registry(): Registry
	{
		$registry = new Registry();

		// Register test Node classes for fixture types
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-article', \Duon\Cms\Tests\Fixtures\Node\TestArticle::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-home', \Duon\Cms\Tests\Fixtures\Node\TestHome::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-block', \Duon\Cms\Tests\Fixtures\Node\TestBlock::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-widget', \Duon\Cms\Tests\Fixtures\Node\TestWidget::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-document', \Duon\Cms\Tests\Fixtures\Node\TestDocument::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('test-media-document', \Duon\Cms\Tests\Fixtures\Node\TestMediaDocument::class);

		// Register dynamically created test types (reuse TestPage for all page types)
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('ordered-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('limit-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('hidden-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('routing-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('nested-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('unpublished-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('create-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('crud-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('update-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('delete-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$registry->tag(\Duon\Cms\Node\Node::class)
			->add('dsl-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);

		return $registry;
	}

	/**
	 * Load SQL fixture files into the test database.
	 *
	 * Note: For SQLite, this uses PDO::exec() to support multi-statement
	 * SQL files. Prepared statements only execute the first statement.
	 *
	 * @param string ...$fixtures Fixture names (without .sql extension)
	 */
	protected function loadFixtures(string ...$fixtures): void
	{
		$config = self::testDbConfig();
		$db = $this->db();
		$suffix = $config->isSqlite() ? '.sqlite' : '';

		foreach ($fixtures as $fixture) {
			// Try driver-specific fixture first
			$path = self::root() . "/tests/Fixtures/data/{$fixture}{$suffix}.sql";

			if (!file_exists($path)) {
				// Fall back to generic fixture
				$path = self::root() . "/tests/Fixtures/data/{$fixture}.sql";
			}

			if (!file_exists($path)) {
				throw new RuntimeException("Fixture file not found: {$path}");
			}

			$sql = file_get_contents($path);

			if ($config->isSqlite()) {
				// SQLite: use exec() for multi-statement SQL
				$db->getConn()->exec($sql);
			} else {
				$db->execute($sql)->run();
			}
		}
	}

	/**
	 * Create a test content type.
	 *
	 * @return int The type ID
	 */
	protected function createTestType(string $handle, string $kind = 'page'): int
	{
		$config = self::testDbConfig();
		$table = $config->isSqlite() ? 'cms_types' : 'cms.types';

		$sql = "INSERT INTO {$table} (handle, kind)
				VALUES (:handle, :kind)
				RETURNING type";

		return $this->db()->execute($sql, [
			'handle' => $handle,
			'kind' => $kind,
		])->one()['type'];
	}

	/**
	 * Create a test node.
	 *
	 * @param array $data Node data (uid, type, content, etc.)
	 * @return int The node ID
	 */
	protected function createTestNode(array $data): int
	{
		$config = self::testDbConfig();
		$table = $config->isSqlite() ? 'cms_nodes' : 'cms.nodes';
		$now = $config->isSqlite() ? "datetime('now')" : 'now()';
		$jsonCast = $config->isSqlite() ? '' : '::jsonb';

		$defaults = [
			'uid' => uniqid('test-'),
			'parent' => null,
			'published' => $config->isSqlite() ? 1 : true,
			'hidden' => $config->isSqlite() ? 0 : false,
			'locked' => $config->isSqlite() ? 0 : false,
			'creator' => 1, // System user
			'editor' => 1,
			'created' => $now,
			'changed' => $now,
			'content' => '{}',
		];

		$data = array_merge($defaults, $data);

		// Convert content array to JSON if needed
		if (is_array($data['content'])) {
			$data['content'] = json_encode($data['content']);
		}

		$sql = "INSERT INTO {$table} (uid, parent, published, hidden, locked, type, creator, editor, created, changed, content)
				VALUES (:uid, :parent, :published, :hidden, :locked, :type, :creator, :editor, :created, :changed, :content{$jsonCast})
				RETURNING node";

		return $this->db()->execute($sql, $data)->one()['node'];
	}

	/**
	 * Create a test user.
	 *
	 * @return int The user ID
	 */
	protected function createTestUser(array $data): int
	{
		$config = self::testDbConfig();
		$table = $config->isSqlite() ? 'cms_users' : 'cms.users';
		$jsonCast = $config->isSqlite() ? '' : '::jsonb';
		$active = $config->isSqlite() ? 1 : true;

		$uid = $data['uid'] ?? uniqid('user-');
		$defaults = [
			'uid' => $uid,
			'username' => $data['username'] ?? $uid,
			'email' => $data['email'] ?? ($uid . '@example.com'),
			'pwhash' => password_hash('password', PASSWORD_ARGON2ID),
			'userrole' => 'editor',
			'active' => $active,
			'data' => ['name' => 'Test User'],
			'creator' => 1,
			'editor' => 1,
		];

		$data = array_merge($defaults, $data);

		if (isset($data['data']) && is_array($data['data'])) {
			$data['data'] = json_encode($data['data']);
		}

		$sql = "INSERT INTO {$table} (uid, username, email, pwhash, userrole, active, data, creator, editor)
				VALUES (:uid, :username, :email, :pwhash, :userrole, :active, :data{$jsonCast}, :creator, :editor)
				RETURNING usr";

		return $this->db()->execute($sql, $data)->one()['usr'];
	}

	/**
	 * Create a URL path for a node.
	 *
	 * @param int $nodeId The node ID
	 * @param string $path The URL path (e.g., '/about/team')
	 * @param string $locale The locale (default: 'en')
	 */
	protected function createTestPath(int $nodeId, string $path, string $locale = 'en'): void
	{
		$config = self::testDbConfig();
		$table = $config->isSqlite() ? 'cms_urlpaths' : 'cms.urlpaths';

		$sql = "INSERT INTO {$table} (node, path, locale, creator, editor)
				VALUES (:node, :path, :locale, 1, 1)";

		$this->db()->execute($sql, [
			'node' => $nodeId,
			'path' => $path,
			'locale' => $locale,
		])->run();
	}

	protected function createContext(): Context
	{
		return new Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	protected function createFinder(): Finder
	{
		return new Finder($this->createContext());
	}

	/**
	 * Get the driver-aware table name for a CMS table.
	 *
	 * @param string $table The base table name (e.g., 'nodes', 'types', 'users')
	 * @return string The qualified table name (e.g., 'cms.nodes' or 'cms_nodes')
	 */
	protected function table(string $table): string
	{
		$config = self::testDbConfig();

		return $config->isSqlite() ? "cms_{$table}" : "cms.{$table}";
	}

	/**
	 * Get the JSONB cast suffix for the current driver.
	 *
	 * @return string '::jsonb' for PostgreSQL, '' for SQLite
	 */
	protected function jsonbCast(): string
	{
		return self::testDbConfig()->isSqlite() ? '' : '::jsonb';
	}

	/**
	 * Check if a row exists by ID.
	 *
	 * @param string $table The base table name (e.g., 'nodes')
	 * @param string $pkColumn The primary key column name
	 * @param int $id The ID to check
	 * @return bool True if the row exists
	 */
	protected function rowExists(string $table, string $pkColumn, int $id): bool
	{
		$tableName = $this->table($table);
		$sql = "SELECT 1 FROM {$tableName} WHERE {$pkColumn} = :id LIMIT 1";

		return $this->db()->execute($sql, ['id' => $id])->one() !== null;
	}

	/**
	 * Fetch a single row from a CMS table by its primary key.
	 *
	 * @param string $table The base table name (e.g., 'nodes')
	 * @param string $pkColumn The primary key column name
	 * @param int $id The primary key value
	 * @return array|null The row data or null if not found
	 */
	protected function fetchRow(string $table, string $pkColumn, int $id): ?array
	{
		$tableName = $this->table($table);
		$sql = "SELECT * FROM {$tableName} WHERE {$pkColumn} = :id";

		return $this->db()->execute($sql, ['id' => $id])->one();
	}

	/**
	 * Delete a row from a CMS table.
	 *
	 * @param string $table The base table name (e.g., 'nodes')
	 * @param string $pkColumn The primary key column name
	 * @param int $id The primary key value
	 */
	protected function deleteRow(string $table, string $pkColumn, int $id): void
	{
		$tableName = $this->table($table);
		$sql = "DELETE FROM {$tableName} WHERE {$pkColumn} = :id";

		$this->db()->execute($sql, ['id' => $id])->run();
	}

	/**
	 * Update JSONB content column for a node.
	 *
	 * @param int $nodeId The node ID
	 * @param array $content The new content
	 */
	protected function updateNodeContent(int $nodeId, array $content): void
	{
		$tableName = $this->table('nodes');
		$cast = $this->jsonbCast();
		$sql = "UPDATE {$tableName} SET content = :content{$cast} WHERE node = :id";

		$this->db()->execute($sql, [
			'id' => $nodeId,
			'content' => json_encode($content),
		])->run();
	}

	/**
	 * Query nodes by type and additional conditions.
	 *
	 * @param int $typeId The type ID
	 * @param array $conditions Additional WHERE conditions
	 * @return array List of matching nodes
	 */
	protected function queryNodesByType(int $typeId, array $conditions = []): array
	{
		$tableName = $this->table('nodes');
		$sql = "SELECT * FROM {$tableName} WHERE type = :type";
		$params = ['type' => $typeId];

		foreach ($conditions as $column => $value) {
			$sql .= " AND {$column} = :{$column}";
			$params[$column] = $value;
		}

		$sql .= ' ORDER BY node';

		return $this->db()->execute($sql, $params)->all();
	}

	/**
	 * Query nodes by parent.
	 *
	 * @param int $parentId The parent node ID
	 * @return array List of child nodes
	 */
	protected function queryNodesByParent(int $parentId): array
	{
		$tableName = $this->table('nodes');
		$sql = "SELECT * FROM {$tableName} WHERE parent = :parent";

		return $this->db()->execute($sql, ['parent' => $parentId])->all();
	}

	/**
	 * Query nodes with JSON field extraction (driver-aware).
	 *
	 * For PostgreSQL uses: content->'field'->'subfield'->>'key'
	 * For SQLite uses: json_extract(content, '$.field.subfield.key')
	 *
	 * @param int $typeId The type ID
	 * @param string $jsonPath The JSON path (dot notation, e.g., 'title.value.en')
	 * @param string $likePattern The LIKE pattern to match
	 * @return array List of matching nodes with uid and extracted value
	 */
	protected function queryNodesByJsonField(int $typeId, string $jsonPath, string $likePattern): array
	{
		$tableName = $this->table('nodes');
		$config = self::testDbConfig();

		if ($config->isSqlite()) {
			$extract = "json_extract(content, '\$.{$jsonPath}')";
		} else {
			// Convert dot notation to PostgreSQL JSON operators
			$parts = explode('.', $jsonPath);
			$last = array_pop($parts);
			$path = "content->'" . implode("'->'", $parts) . "'->>'{$last}'";
			$extract = $path;
		}

		$sql = "SELECT uid, {$extract} as extracted_value
				FROM {$tableName}
				WHERE type = :type
				AND {$extract} LIKE :pattern";

		return $this->db()->execute($sql, [
			'type' => $typeId,
			'pattern' => $likePattern,
		])->all();
	}

	/**
	 * Fetch a user by ID.
	 *
	 * @param int $userId The user ID
	 * @return array|null The user data or null
	 */
	protected function fetchUser(int $userId): ?array
	{
		$tableName = $this->table('users');
		$sql = "SELECT uid, username, email, userrole, active, data FROM {$tableName} WHERE usr = :usr";

		return $this->db()->execute($sql, ['usr' => $userId])->one();
	}
}
