<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\End2End;

use Duon\Cms\Tests\End2EndTestCase;
use PHPUnit\Framework\Attributes\Group as G;

/**
 * End-to-end tests for Node CRUD operations through HTTP API.
 *
 * @internal
 *
 * @coversNothing
 */
final class NodeCrudTest extends End2EndTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->loadFixtures('basic-types', 'sample-nodes');
	}

	public function testGetNodeList(): void
	{
		$this->authenticateAs('editor');

		$response = $this->makeRequest('GET', '/panel/api/nodes', [
			'query' => ['type' => 'test-article'],
		]);

		$this->assertResponseOk($response);
	}

	public function testGetSingleNode(): void
	{
		$this->authenticateAs('editor');

		$typeId = $this->createTestType('crud-test-page', 'page');
		$nodePath = '/test/crud-test-node';
		$this->createTestNode([
			'uid' => 'crud-test-node',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Test Node']],
			],
		]);
		$this->createTestPath($this->createdNodeIds[count($this->createdNodeIds) - 1], $nodePath);

		$response = $this->makeRequest('GET', '/panel/api/node/crud-test-node');

		$this->assertResponseOk($response);

		$payload = $this->getJsonResponse($response);
		$this->assertSame('crud-test-node', $payload['uid'] ?? null);
		$this->assertSame('Test Node', $payload['title'] ?? null);
		$this->assertArrayHasKey('fields', $payload);
		$this->assertArrayHasKey('paths', $payload);
		$this->assertSame($nodePath, $payload['paths']['en'] ?? null);
	}

	public function testGetSingleNodeRequiresAuthentication(): void
	{
		$typeId = $this->createTestType('crud-test-page', 'page');
		$nodePath = '/test/unauth-node';
		$this->createTestNode([
			'uid' => 'crud-test-unauth-node',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Unauth Node']],
			],
		]);
		$this->createTestPath($this->createdNodeIds[count($this->createdNodeIds) - 1], $nodePath);

		$response = $this->makeRequest('GET', '/panel/api/node/crud-test-unauth-node');

		$this->assertResponseStatus(401, $response);
	}

	public function testCreateNode(): void
	{
		$this->authenticateAs('editor');

		$uid = 'new-test-node-' . uniqid();
		$this->createTestType('create-test-page', 'page');
		$nodeData = [
			'uid' => $uid,
			'published' => true,
			'paths' => [
				'en' => '/test/' . $uid,
			],
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'New Node']],
			],
		];

		$response = $this->makeRequest('POST', '/panel/api/node/create-test-page', [
			'body' => $nodeData,
		]);

		$this->assertResponseOk($response);
	}

	public function testUpdateNode(): void
	{
		$this->authenticateAs('editor');

		$typeId = $this->createTestType('update-test-page', 'page');
		$uid = 'update-test-node-' . uniqid();
		$this->createTestNode([
			'uid' => $uid,
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Original Title']],
			],
		]);
		$this->createTestPath($this->createdNodeIds[count($this->createdNodeIds) - 1], '/test/' . $uid);

		$updateData = [
			'uid' => $uid,
			'published' => true,
			'locked' => false,
			'hidden' => false,
			'paths' => [
				'en' => '/test/' . $uid,
			],
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Updated Title']],
			],
		];

		$response = $this->makeRequest('PUT', "/panel/api/node/{$uid}", [
			'body' => $updateData,
		]);

		$this->assertResponseOk($response);
	}

	public function testDeleteNode(): void
	{
		$this->authenticateAs('editor');

		$typeId = $this->createTestType('delete-test-page', 'page');
		$uid = 'delete-test-node-' . uniqid();
		$this->createTestNode([
			'uid' => $uid,
			'type' => $typeId,
		]);

		$response = $this->makeRequest('DELETE', "/panel/api/node/{$uid}", [
			'headers' => ['Accept' => 'application/json'],
		]);

		$this->assertResponseOk($response);
	}
}
