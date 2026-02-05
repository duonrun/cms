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

		$node = $this->fetchRow('nodes', 'node', $nodeId);

		$this->assertNotNull($node);
		$this->assertEquals('integration-test-node-1', $node['uid']);
		$this->assertEquals($typeId, $node['type']);
		$this->assertTruthy($node['published']);
		$this->assertFalsy($node['hidden']);

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

		$node = $this->fetchRow('nodes', 'node', $nodeId);

		$this->assertNotNull($node);
		$this->assertTruthy($node['published']); // Default is true
		$this->assertFalsy($node['hidden']); // Default is false
		$this->assertFalsy($node['locked']); // Default is false
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

		$this->updateNodeContent($nodeId, $updatedContent);

		$node = $this->fetchRow('nodes', 'node', $nodeId);

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

		// Query for published nodes only
		$config = self::testDbConfig();
		$publishedValue = $config->isSqlite() ? 1 : true;
		$nodes = $this->queryNodesByType($typeId, ['published' => $publishedValue]);

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

		$children = $this->queryNodesByParent($parentId);

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

		$this->assertTrue($this->rowExists('nodes', 'node', $nodeId));

		$this->deleteRow('nodes', 'node', $nodeId);

		$this->assertFalse($this->rowExists('nodes', 'node', $nodeId));
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

		$nodes = $this->queryNodesByJsonField($typeId, 'title.value.en', '%Second%');

		$this->assertCount(1, $nodes);
		$this->assertEquals('jsonb-node-2', $nodes[0]['uid']);
		$this->assertEquals('Second Title', $nodes[0]['extracted_value']);
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

		$user = $this->fetchUser($userId);

		$this->assertNotNull($user);
		$this->assertSame('integration-test-user', $user['uid']);
		$this->assertSame('integration-user', $user['username']);
		$this->assertSame('integration-user@example.com', $user['email']);
		$this->assertSame('admin', $user['userrole']);
		$this->assertTruthy($user['active']);
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

	/**
	 * Assert that a value is truthy (works with both boolean true and integer 1).
	 */
	private function assertTruthy(mixed $value): void
	{
		$this->assertTrue((bool) $value, 'Expected truthy value');
	}

	/**
	 * Assert that a value is falsy (works with both boolean false and integer 0).
	 */
	private function assertFalsy(mixed $value): void
	{
		$this->assertFalse((bool) $value, 'Expected falsy value');
	}
}
