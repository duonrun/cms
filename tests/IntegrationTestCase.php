<?php

declare(strict_types=1);

namespace Duon\Cms\Tests;

use Duon\Cms\CmsDatabase;
use Duon\Cms\Context;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Dialect\SqlDialectFactory;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Tests\Support\TestDbConfig;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use Duon\Registry\Registry;
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
		$driver = TestDbConfig::driver();

		if ($driver === 'sqlite') {
			TestDbConfig::initSqliteFile();
		}

		self::$sharedConnection = TestDbConfig::connection();
		$db = new Database(self::$sharedConnection);

		if ($driver === 'sqlite') {
			self::applySqliteMigrations($db);

			return;
		}

		// Check if migrations table exists
		$tableExists = $db->execute(
			"SELECT EXISTS (
				SELECT FROM information_schema.tables
				WHERE table_schema = 'public'
				AND table_name = 'migrations'
			) as exists",
		)->one()['exists'] ?? false;

		if (!$tableExists) {
			$diagnostics = self::databaseDiagnostics();
			echo "\n⚠ Test database not initialized.\n{$diagnostics}\nRun: ./run recreate-db && ./run migrate --apply\n\n";

			throw new RuntimeException(
				"Test database not initialized. {$diagnostics}Run: ./run recreate-db && ./run migrate --apply",
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
			$diagnostics = self::databaseDiagnostics();
			echo "\n⚠ Migrations not applied.\n{$diagnostics}\nRun: ./run migrate --apply\n\n";

			throw new RuntimeException(
				"Migrations not applied to test database. {$diagnostics}Run: ./run migrate --apply",
			);
		}
	}

	private static function applySqliteMigrations(Database $db): void
	{
		$installDirs = TestDbConfig::migrationDirsForDriver('install');
		$updateDirs = TestDbConfig::migrationDirsForDriver('default');

		self::runSqlMigrations($db, $installDirs);
		self::runSqlMigrations($db, $updateDirs);
	}

	private static function runSqlMigrations(Database $db, array $dirs): void
	{
		foreach (self::collectMigrationFiles($dirs) as $migration) {
			$script = file_get_contents($migration);

			if ($script === false) {
				throw new RuntimeException("Migration file not readable: {$migration}");
			}

			if (trim($script) === '') {
				continue;
			}

			$result = $db->getConn()->exec($script);

			if ($result === false) {
				throw new RuntimeException("Migration failed: {$migration}");
			}
		}
	}

	/**
	 * @return list<string>
	 */
	private static function collectMigrationFiles(array $dirs): array
	{
		$files = [];

		foreach ($dirs as $dir) {
			foreach (glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') as $file) {
				if (is_string($file) && is_file($file)) {
					$files[] = $file;
				}
			}
		}

		usort($files, fn(string $a, string $b): int => strcmp(basename($a), basename($b)));

		return $files;
	}

	private static function databaseDiagnostics(): string
	{
		$driver = TestDbConfig::driver();
		$dsn = TestDbConfig::sanitizedDsn();
		$installDirs = TestDbConfig::migrationDirsForDriver('install');
		$updateDirs = TestDbConfig::migrationDirsForDriver('default');

		return sprintf(
			"Driver: %s\nDSN: %s\nInstall migrations: %s\nUpdate migrations: %s\n",
			$driver,
			$dsn,
			implode(', ', $installDirs),
			implode(', ', $updateDirs),
		);
	}

	protected function setUp(): void
	{
		parent::setUp();

		// Begin transaction if this test uses them
		if ($this->useTransactions) {
			$this->testDb = new CmsDatabase($this->conn(), $this->config());
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
		return TestDbConfig::connection();
	}

	public function db(): Database
	{
		// If using transactions, return the same database instance
		if ($this->useTransactions && $this->testDb !== null) {
			return $this->testDb;
		}

		return new CmsDatabase($this->conn(), $this->config());
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
	 * @param string ...$fixtures Fixture names (without .sql extension)
	 */
	protected function loadFixtures(string ...$fixtures): void
	{
		$db = $this->db();
		$driver = TestDbConfig::driver();

		foreach ($fixtures as $fixture) {
			$driverPath = self::root() . "/tests/Fixtures/data/{$driver}/{$fixture}.sql";
			$path = is_file($driverPath)
				? $driverPath
				: self::root() . "/tests/Fixtures/data/{$fixture}.sql";

			if (!file_exists($path)) {
				throw new RuntimeException("Fixture file not found: {$path}");
			}

			$sql = file_get_contents($path);

			if ($driver === 'sqlite') {
				$result = $db->getConn()->exec($sql);

				if ($result === false) {
					throw new RuntimeException("Failed to apply sqlite fixture: {$path}");
				}
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
		$table = $this->table('types');
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
		$defaults = [
			'uid' => uniqid('test-'),
			'parent' => null,
			'published' => true,
			'hidden' => false,
			'locked' => false,
			'creator' => 1, // System user
			'editor' => 1,
			'created' => null,
			'changed' => null,
			'content' => '{}',
		];

		$data = array_merge($defaults, $data);

		// Convert content array to JSON if needed
		if (is_array($data['content'])) {
			$data['content'] = json_encode($data['content']);
		}

		$table = $this->table('nodes');
		$jsonCast = $this->jsonCast();
		$now = $this->now();
		$sql = "INSERT INTO {$table} (uid, parent, published, hidden, locked, type, creator, editor, created, changed, content)
				VALUES (:uid, :parent, :published, :hidden, :locked, :type, :creator, :editor,
					COALESCE(:created, {$now}), COALESCE(:changed, {$now}), :content{$jsonCast})
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

		$table = $this->table('users');
		$jsonCast = $this->jsonCast();
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
		$table = $this->table('urlpaths');
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

	protected function dialect(): SqlDialect
	{
		return SqlDialectFactory::fromDriver($this->db()->getPdoDriver());
	}

	protected function table(string $name): string
	{
		return $this->dialect()->table($name);
	}

	protected function jsonCast(): string
	{
		return $this->dialect()->driver() === 'pgsql' ? '::jsonb' : '';
	}

	protected function now(): string
	{
		return $this->dialect()->now();
	}

	protected function tableExists(string $table): bool
	{
		$driver = $this->dialect()->driver();

		if ($driver === 'sqlite') {
			$result = $this->db()->execute(
				"SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
				['name' => $table],
			)->one();

			return !empty($result);
		}

		$result = $this->db()->execute(
			"SELECT to_regclass(:name) AS name",
			['name' => $table],
		)->one();

		return !empty($result['name']);
	}
}
