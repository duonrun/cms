<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;

final class NodeTest extends IntegrationTestCase
{
	public function testCreateAndRetrieveNode(): void
	{
		$typeId = $this->createTestType('integration-test-page', 'page');
		$content = [
			'title' => [
				'type' => 'text',
				'value' => ['de' => 'Testseite', 'en' => 'Test Page'],
			],
			'body' => [
				'type' => 'html',
				'value' => ['de' => '<p>Deutscher Inhalt</p>', 'en' => '<p>English content</p>'],
			],
		];

		$nodeId = $this->createTestNode([
			'uid' => 'integration-test-node-1',
			'type' => $typeId,
			'content' => $content,
		]);

		$nodesTable = $this->table('nodes');
		$node = $this->db()->execute(
			"SELECT * FROM {$nodesTable} WHERE node = :id",
			['id' => $nodeId],
		)->one();

		$this->assertNotNull($node);
		$this->assertEquals('integration-test-node-1', $node['uid']);
		$this->assertEquals($typeId, $node['type']);
		$this->assertTrue($node['published']);
		$this->assertFalse($node['hidden']);

		$contentData = json_decode($node['content'], true);
		$this->assertEquals('Testseite', $contentData['title']['value']['de']);
		$this->assertEquals('Test Page', $contentData['title']['value']['en']);
	}

	public function testCreateNodeWithDefaults(): void
	{
		$typeId = $this->createTestType('default-test-page', 'page');

		$nodeId = $this->createTestNode([
			'type' => $typeId,
		]);

		$nodesTable = $this->table('nodes');
		$node = $this->db()->execute(
			"SELECT * FROM {$nodesTable} WHERE node = :id",
			['id' => $nodeId],
		)->one();

		$this->assertNotNull($node);
		$this->assertTrue($node['published']); // Default is true
		$this->assertFalse($node['hidden']); // Default is false
		$this->assertFalse($node['locked']); // Default is false
		$this->assertEquals(1, $node['creator']); // System user
		$this->assertEquals(1, $node['editor']); // System user
	}

	public function testUpdateNodeContent(): void
	{
		$typeId = $this->createTestType('update-test-page', 'page');
		$initialContent = [
			'title' => ['type' => 'text', 'value' => ['en' => 'Initial Title']],
		];

		$nodeId = $this->createTestNode([
			'uid' => 'update-test-node',
			'type' => $typeId,
			'content' => $initialContent,
		]);

		$updatedContent = [
			'title' => ['type' => 'text', 'value' => ['en' => 'Updated Title']],
			'subtitle' => ['type' => 'text', 'value' => ['en' => 'New Subtitle']],
		];

		$nodesTable = $this->table('nodes');
		$jsonCast = $this->jsonCast();
		$this->db()->execute(
			"UPDATE {$nodesTable} SET content = :content{$jsonCast} WHERE node = :id",
			['id' => $nodeId, 'content' => json_encode($updatedContent)],
		)->run();

		$node = $this->db()->execute(
			"SELECT content FROM {$nodesTable} WHERE node = :id",
			['id' => $nodeId],
		)->one();

		$contentData = json_decode($node['content'], true);
		$this->assertEquals('Updated Title', $contentData['title']['value']['en']);
		$this->assertEquals('New Subtitle', $contentData['subtitle']['value']['en']);
	}

	public function testQueryNodesByType(): void
	{
		$typeId = $this->createTestType('query-test-page', 'page');

		$this->createTestNode(['uid' => 'query-node-1', 'type' => $typeId, 'published' => true]);
		$this->createTestNode(['uid' => 'query-node-2', 'type' => $typeId, 'published' => true]);
		$this->createTestNode(['uid' => 'query-node-3', 'type' => $typeId, 'published' => false]);

		$nodesTable = $this->table('nodes');
		$nodes = $this->db()->execute(
			"SELECT * FROM {$nodesTable} WHERE type = :type AND published = :published ORDER BY node",
			['type' => $typeId, 'published' => true],
		)->all();

		$this->assertCount(2, $nodes);
		$this->assertEquals('query-node-1', $nodes[0]['uid']);
		$this->assertEquals('query-node-2', $nodes[1]['uid']);
	}

	public function testNodeHierarchy(): void
	{
		$typeId = $this->createTestType('hierarchy-test-page', 'page');

		$parentId = $this->createTestNode([
			'uid' => 'hierarchy-parent',
			'type' => $typeId,
		]);

		$childId = $this->createTestNode([
			'uid' => 'hierarchy-child',
			'type' => $typeId,
			'parent' => $parentId,
		]);

		$nodesTable = $this->table('nodes');
		$children = $this->db()->execute(
			"SELECT * FROM {$nodesTable} WHERE parent = :parent",
			['parent' => $parentId],
		)->all();

		$this->assertCount(1, $children);
		$this->assertEquals('hierarchy-child', $children[0]['uid']);
		$this->assertEquals($parentId, $children[0]['parent']);
	}

	public function testDeleteNode(): void
	{
		$typeId = $this->createTestType('delete-test-page', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'delete-test-node',
			'type' => $typeId,
		]);

		$nodesTable = $this->table('nodes');
		$exists = $this->db()->execute(
			"SELECT EXISTS(SELECT 1 FROM {$nodesTable} WHERE node = :id) as exists",
			['id' => $nodeId],
		)->one()['exists'];
		$this->assertTrue($exists);

		$this->db()->execute(
			"DELETE FROM {$nodesTable} WHERE node = :id",
			['id' => $nodeId],
		)->run();

		$exists = $this->db()->execute(
			"SELECT EXISTS(SELECT 1 FROM {$nodesTable} WHERE node = :id) as exists",
			['id' => $nodeId],
		)->one()['exists'];
		$this->assertFalse($exists);
	}

	public function testNodeJsonbQuerying(): void
	{
		$typeId = $this->createTestType('jsonb-test-page', 'page');

		$this->createTestNode([
			'uid' => 'jsonb-node-1',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'First Title']],
			],
		]);

		$this->createTestNode([
			'uid' => 'jsonb-node-2',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Second Title']],
			],
		]);

		$nodesTable = $this->table('nodes');
		$pattern = '%Second%';
		if ($this->dialect()->driver() === 'pgsql') {
			$sql = "SELECT uid, content->'title'->'value'->>'en' as title
			 FROM {$nodesTable}
			 WHERE type = :type
			 AND content->'title'->'value'->>'en' LIKE :pattern";
		} else {
			$sql = "SELECT uid, json_extract(content, '$.title.value.en') as title
			 FROM {$nodesTable}
			 WHERE type = :type
			 AND json_extract(content, '$.title.value.en') LIKE :pattern";
		}

		$nodes = $this->db()->execute(
			$sql,
			['type' => $typeId, 'pattern' => $pattern],
		)->all();

		$this->assertCount(1, $nodes);
		$this->assertEquals('jsonb-node-2', $nodes[0]['uid']);
		$this->assertEquals('Second Title', $nodes[0]['title']);
	}

	public function testCreateTestUserMatchesSchema(): void
	{
		$userId = $this->createTestUser([
			'uid' => 'integration-test-user',
			'username' => 'integration-user',
			'email' => 'integration-user@example.com',
			'userrole' => 'admin',
			'data' => ['name' => 'Integration User'],
		]);

		$usersTable = $this->table('users');
		$user = $this->db()->execute(
			"SELECT uid, username, email, userrole, active, data FROM {$usersTable} WHERE usr = :usr",
			['usr' => $userId],
		)->one();

		$this->assertNotNull($user);
		$this->assertSame('integration-test-user', $user['uid']);
		$this->assertSame('integration-user', $user['username']);
		$this->assertSame('integration-user@example.com', $user['email']);
		$this->assertSame('admin', $user['userrole']);
		$this->assertTrue($user['active']);
		$data = json_decode($user['data'], true);
		$this->assertSame('Integration User', ($data['name'] ?? null));
	}

	public function testPagePathRequiresDefaultLocale(): void
	{
		$context = $this->createContext();
		$finder = $this->createFinder();

		$page = new \Duon\Cms\Tests\Fixtures\Node\TestPage(
			$context,
			$finder,
			['content' => []],
		);

		$this->throws(
			\Duon\Core\Exception\HttpBadRequest::class,
			'Bad Request',
		);

		$page->create([
			'uid' => 'path-missing-default',
			'published' => true,
			'locked' => false,
			'hidden' => false,
			'paths' => [
				'de' => '/nur-de',
			],
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Title']],
			],
		]);
	}
}
