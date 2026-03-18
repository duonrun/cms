<?php

declare(strict_types=1);

namespace Duon\Cms\Tests;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Node\Types;
use Duon\Cms\Plugin;
use Duon\Cms\Renderer;
use Duon\Container\Container;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use PDO;
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
		if (self::testDriver() === 'sqlite') {
			self::initializeSqliteDatabase();

			return;
		}

		self::$sharedConnection = new Connection(
			self::testDsn(),
			self::testSqlDirs(),
			self::testMigrationDirs(),
			fetchMode: PDO::FETCH_ASSOC,
			print: false,
		);

		$db = new Database(self::$sharedConnection);

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
			$this->testDb = $this->createDatabase();
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
		$connection = new Connection(
			self::testDsn(),
			self::testSqlDirs(),
			self::testMigrationDirs(),
			fetchMode: PDO::FETCH_ASSOC,
			print: false,
		);

		if (self::testDriver() === 'sqlite') {
			$connection->setMigrationsTable('migrations');
		}

		return $connection;
	}

	protected static function initializeSqliteDatabase(): void
	{
		$path = substr(self::testDsn(), strlen('sqlite:'));

		if ($path === false || $path === '') {
			throw new RuntimeException('Invalid SQLite test DSN');
		}

		if (file_exists($path) && !unlink($path)) {
			throw new RuntimeException('Could not reset SQLite test database: ' . $path);
		}

		$schemaPath = self::sqliteSchemaPath();

		if (file_exists($schemaPath) && !unlink($schemaPath)) {
			throw new RuntimeException('Could not reset SQLite CMS schema: ' . $schemaPath);
		}

		self::$sharedConnection = new Connection(
			self::testDsn(),
			self::testSqlDirs(),
			self::testMigrationDirs(),
			fetchMode: PDO::FETCH_ASSOC,
			print: false,
		);
		self::$sharedConnection->setMigrationsTable('migrations');

		$db = new Database(self::$sharedConnection);
		self::attachSqliteSchema($db);

		foreach (self::testMigrationDirs()['install'] as $dir) {
			$files = glob($dir . '/*.sql');

			if (!is_array($files)) {
				continue;
			}

			sort($files);

			foreach ($files as $file) {
				$sql = file_get_contents($file);

				if (!is_string($sql) || trim($sql) === '') {
					continue;
				}

				$db->getConn()->exec($sql);
			}
		}
	}

	public function db(): Database
	{
		// If using transactions, return the same database instance
		if ($this->useTransactions && $this->testDb !== null) {
			return $this->testDb;
		}

		return $this->createDatabase();
	}

	private function createDatabase(): Database
	{
		$db = new Database($this->conn());

		if (self::testDriver() === 'sqlite') {
			self::attachSqliteSchema($db);
		}

		return $db;
	}

	public function container(): Container
	{
		$container = new Container();

		// Register test Node classes for fixture types
		$container->tag(Plugin::NODE_TAG)
			->add('test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-article', \Duon\Cms\Tests\Fixtures\Node\TestArticle::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-home', \Duon\Cms\Tests\Fixtures\Node\TestHome::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-block', \Duon\Cms\Tests\Fixtures\Node\TestBlock::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-widget', \Duon\Cms\Tests\Fixtures\Node\TestWidget::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-document', \Duon\Cms\Tests\Fixtures\Node\TestDocument::class);
		$container->tag(Plugin::NODE_TAG)
			->add('test-media-document', \Duon\Cms\Tests\Fixtures\Node\TestMediaDocument::class);

		// Register dynamically created test types (reuse TestPage for all page types)
		$container->tag(Plugin::NODE_TAG)
			->add('ordered-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('limit-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('hidden-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('routing-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('nested-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('unpublished-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('create-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('crud-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('update-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('delete-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Plugin::NODE_TAG)
			->add('renderable-test-page', \Duon\Cms\Tests\Fixtures\Node\TestPage::class);
		$container->tag(Renderer::class)
			->add('template', \Duon\Cms\Tests\Fixtures\TestRenderer::class);

		return $container;
	}

	/**
	 * Load SQL fixture files into the test database.
	 *
	 * @param string ...$fixtures Fixture names (without .sql extension)
	 */
	protected function loadFixtures(string ...$fixtures): void
	{
		$db = $this->db();

		foreach ($fixtures as $fixture) {
			$path = self::root() . "/tests/Fixtures/data/{$fixture}.sql";

			if (self::testDriver() === 'sqlite') {
				$sqlitePath = self::root() . "/tests/Fixtures/data/sqlite/{$fixture}.sql";

				if (file_exists($sqlitePath)) {
					$path = $sqlitePath;
				}
			}

			if (!file_exists($path)) {
				throw new RuntimeException("Fixture file not found: {$path}");
			}

			$sql = file_get_contents($path);

			if (self::testDriver() === 'sqlite') {
				$db->getConn()->exec($sql);

				continue;
			}

			$db->execute($sql)->run();
		}
	}

	/**
	 * Create a test content type.
	 *
	 * @return int The type ID
	 */
	protected function createTestType(string $handle): int
	{
		if (self::testDriver() === 'sqlite') {
			$this->db()->execute('INSERT INTO cms.types (handle) VALUES (:handle)', [
				'handle' => $handle,
			])->run();

			return (int) $this->db()->getConn()->lastInsertId();
		}

		$sql = "INSERT INTO cms.types (handle)
				VALUES (:handle)
				RETURNING type";

		return $this->db()->execute($sql, [
			'handle' => $handle,
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
		$defaults = [
			'uid' => uniqid('test-'),
			'parent' => null,
			'published' => true,
			'hidden' => false,
			'locked' => false,
			'creator' => 1, // System user
			'editor' => 1,
			'created' => 'now()',
			'changed' => 'now()',
			'content' => '{}',
		];

		$data = array_merge($defaults, $data);

		// Convert content array to JSON if needed
		if (is_array($data['content'])) {
			$data['content'] = json_encode($data['content']);
		}

		if (self::testDriver() === 'sqlite') {
			$sql = "INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, created, changed, content)
					VALUES (:uid, :parent, :published, :hidden, :locked, :type, :creator, :editor, :created, :changed, :content)";
			$this->db()->execute($sql, $data)->run();

			return (int) $this->db()->getConn()->lastInsertId();
		}

		$sql = "INSERT INTO cms.nodes (uid, parent, published, hidden, locked, type, creator, editor, created, changed, content)
				VALUES (:uid, :parent, :published, :hidden, :locked, :type, :creator, :editor, :created, :changed, :content::jsonb)
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
		$uid = $data['uid'] ?? uniqid('user-');
		$defaults = [
			'uid' => $uid,
			'username' => $data['username'] ?? $uid,
			'email' => $data['email'] ?? ($uid . '@example.com'),
			'pwhash' => password_hash('password', PASSWORD_ARGON2ID),
			'userrole' => 'editor',
			'active' => true,
			'data' => ['name' => 'Test User'],
			'creator' => 1,
			'editor' => 1,
		];

		$data = array_merge($defaults, $data);

		if (isset($data['data']) && is_array($data['data'])) {
			$data['data'] = json_encode($data['data']);
		}

		if (self::testDriver() === 'sqlite') {
			$sql = "INSERT INTO cms.users (uid, username, email, pwhash, userrole, active, data, creator, editor)
					VALUES (:uid, :username, :email, :pwhash, :userrole, :active, :data, :creator, :editor)";
			$this->db()->execute($sql, $data)->run();

			return (int) $this->db()->getConn()->lastInsertId();
		}

		$sql = "INSERT INTO cms.users (uid, username, email, pwhash, userrole, active, data, creator, editor)
				VALUES (:uid, :username, :email, :pwhash, :userrole, :active, :data::jsonb, :creator, :editor)
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
		$sql = "INSERT INTO cms.urlpaths (node, path, locale, creator, editor)
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
			$this->container(),
			$this->factory(),
		);
	}

	protected function createCms(): Cms
	{
		return new Cms($this->createContext(), new Types());
	}
}
