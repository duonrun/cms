<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;
use PDO;

/**
 * Smoke test for SQLite test database lifecycle.
 *
 * Verifies that when running with CMS_TEST_DRIVER=sqlite:
 * - Migrations are applied automatically
 * - Basic CRUD operations work correctly
 * - The test database is properly initialized
 *
 * This test is skipped when running with PostgreSQL driver.
 *
 * @internal
 *
 * @coversNothing
 */
final class SqliteLifecycleTest extends IntegrationTestCase
{
	protected function setUp(): void
	{
		if (!self::testDbConfig()->isSqlite()) {
			$this->markTestSkipped('SQLite lifecycle test only runs with CMS_TEST_DRIVER=sqlite');
		}

		parent::setUp();
	}

	public function testMigrationsAreAppliedAutomatically(): void
	{
		$db = $this->db();

		// Check that core tables exist
		$result = $db->execute(
			"SELECT name FROM sqlite_master WHERE type='table' AND name='cms_nodes'",
		)->one();

		$this->assertNotNull($result, 'cms_nodes table should exist after migrations');
		$this->assertSame('cms_nodes', $result['name']);
	}

	public function testSeedDataIsPresent(): void
	{
		$db = $this->db();

		// Check that system user exists
		$user = $db->execute(
			"SELECT uid, userrole FROM cms_users WHERE uid = '0000000000000'",
		)->one();

		$this->assertNotNull($user, 'System user should exist');
		$this->assertSame('system', $user['userrole']);

		// Check that user roles exist
		$roles = $db->execute('SELECT userrole FROM cms_userroles ORDER BY userrole')->all();
		$roleNames = array_column($roles, 'userrole');

		$this->assertContains('system', $roleNames);
		$this->assertContains('admin', $roleNames);
		$this->assertContains('editor', $roleNames);
	}

	public function testBasicCrudOperations(): void
	{
		// Create a test type
		$typeId = $this->createTestType('sqlite-crud-test', 'page');
		$this->assertIsInt($typeId);
		$this->assertGreaterThan(0, $typeId);

		// Create a test node
		$nodeId = $this->createTestNode([
			'uid' => 'sqlite-crud-test-node',
			'type' => $typeId,
			'content' => ['title' => ['value' => ['en' => 'Test Node']]],
		]);
		$this->assertIsInt($nodeId);
		$this->assertGreaterThan(0, $nodeId);

		// Verify the node was created
		$db = $this->db();
		$node = $db->execute(
			'SELECT uid, type, content FROM cms_nodes WHERE node = :node',
			['node' => $nodeId],
		)->one();

		$this->assertNotNull($node);
		$this->assertSame('sqlite-crud-test-node', $node['uid']);
		$this->assertSame($typeId, (int) $node['type']);

		// Verify JSON content is accessible
		$content = json_decode($node['content'], true);
		$this->assertIsArray($content);
		$this->assertSame('Test Node', $content['title']['value']['en']);

		// Create and verify URL path
		$this->createTestPath($nodeId, '/sqlite-crud-test', 'en');
		$path = $db->execute(
			'SELECT path, locale FROM cms_urlpaths WHERE node = :node',
			['node' => $nodeId],
		)->one();

		$this->assertNotNull($path);
		$this->assertSame('/sqlite-crud-test', $path['path']);
		$this->assertSame('en', $path['locale']);
	}

	public function testJsonFunctionsWork(): void
	{
		$db = $this->db();

		// Create a type and node for testing
		$typeId = $this->createTestType('json-func-test', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'json-func-test-node',
			'type' => $typeId,
			'content' => [
				'title' => ['value' => ['en' => 'English Title', 'de' => 'Deutscher Titel']],
				'active' => ['value' => true],
			],
		]);

		// Test json_extract works correctly
		$result = $db->execute(
			"SELECT json_extract(content, '$.title.value.en') as title FROM cms_nodes WHERE node = :node",
			['node' => $nodeId],
		)->one();

		$this->assertSame('English Title', $result['title']);

		// Test json_type works for type checking
		$result = $db->execute(
			"SELECT json_type(content, '$.active.value') as jtype FROM cms_nodes WHERE node = :node",
			['node' => $nodeId],
		)->one();

		// SQLite returns true as integer 1 in JSON
		$this->assertNotNull($result['jtype']);
	}

	public function testTransactionIsolationWorks(): void
	{
		$db = $this->db();

		// The test starts with a transaction (from IntegrationTestCase)
		// Insert some data
		$typeId = $this->createTestType('txn-test', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'txn-test-node',
			'type' => $typeId,
			'content' => '{}',
		]);

		// Verify data is visible within the transaction
		$node = $db->execute(
			'SELECT uid FROM cms_nodes WHERE node = :node',
			['node' => $nodeId],
		)->one();

		$this->assertNotNull($node);
		$this->assertSame('txn-test-node', $node['uid']);

		// The transaction will be rolled back in tearDown(), so this data won't persist
	}
}
