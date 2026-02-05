<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FulltextTest extends IntegrationTestCase
{
	public function testCleanRemovesOrphanedEntries(): void
	{
		if ($this->db()->getPdoDriver() !== 'pgsql') {
			$this->markTestSkipped('Fulltext clean test requires PostgreSQL');
		}

		$db = $this->db();

		// Create a type and node using helper methods
		$typeId = $this->createTestType('fulltext-clean-test', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'fulltext-clean-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'Test Page'],
		]);

		// Insert a fulltext entry for the node
		$db->execute(
			"INSERT INTO cms.fulltext (node, locale, document) VALUES (:node, 'en', to_tsvector('english', 'test content'))",
			['node' => $nodeId],
		)->run();

		// Verify the fulltext entry exists
		$count = $db->execute(
			'SELECT COUNT(*) as cnt FROM cms.fulltext WHERE node = :node',
			['node' => $nodeId],
		)->one();
		$this->assertEquals(1, $count['cnt']);

		// Unpublish the node (should make fulltext entry orphaned after clean)
		// Use explicit SQL with literal false for PostgreSQL boolean
		$db->execute(
			'UPDATE ' . $this->table('nodes') . ' SET published = false WHERE node = :node',
			['node' => $nodeId],
		)->run();

		// Verify the node was unpublished
		$node = $db->execute(
			'SELECT published FROM ' . $this->table('nodes') . ' WHERE node = :node',
			['node' => $nodeId],
		)->one();
		$this->assertFalse($node['published']);

		// Run clean - must call run() to execute the DELETE
		$db->fulltext->clean()->run();

		// Verify the fulltext entry was removed
		$count = $db->execute(
			'SELECT COUNT(*) as cnt FROM cms.fulltext WHERE node = :node',
			['node' => $nodeId],
		)->one();
		$this->assertEquals(0, $count['cnt']);
	}

	public function testNodesQueryReturnsPublishedNodes(): void
	{
		if ($this->db()->getPdoDriver() !== 'pgsql') {
			$this->markTestSkipped('Fulltext nodes test requires PostgreSQL');
		}

		$db = $this->db();
		$typeId = $this->createTestType('fulltext-nodes-test', 'page');

		// Insert a published node
		$this->createTestNode([
			'uid' => 'fulltext-published-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'Published'],
		]);

		// Insert an unpublished node
		$this->createTestNode([
			'uid' => 'fulltext-unpublished-node',
			'type' => $typeId,
			'published' => false,
			'content' => ['title' => 'Unpublished'],
		]);

		// Query nodes for fulltext indexing
		$nodes = $db->fulltext->nodes()->all();

		// Verify we get results with expected structure
		$this->assertNotEmpty($nodes);
		$this->assertArrayHasKey('content', $nodes[0]);
		$this->assertArrayHasKey('handle', $nodes[0]);
	}

	public function testUpsertCreatesFulltextEntry(): void
	{
		if ($this->db()->getPdoDriver() !== 'pgsql') {
			$this->markTestSkipped('Fulltext upsert test requires PostgreSQL');
		}

		$db = $this->db();
		$typeId = $this->createTestType('fulltext-upsert-test', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'fulltext-upsert-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'Upsert Test'],
		]);

		// Upsert fulltext entry - must call run() to execute the INSERT
		$db->fulltext->upsert([
			'node' => $nodeId,
			'locale' => 'en',
			'config' => 'english',
			'text_a' => 'Title text weight A',
			'text_b' => 'Summary text weight B',
			'text_c' => 'Body text weight C',
			'text_d' => 'Metadata weight D',
		])->run();

		// Verify the fulltext entry exists
		$row = $db->execute(
			'SELECT node, locale, document::text FROM cms.fulltext WHERE node = :node',
			['node' => $nodeId],
		)->one();

		$this->assertNotNull($row);
		$this->assertEquals($nodeId, $row['node']);
		$this->assertEquals('en', $row['locale']);
		$this->assertStringContainsString('titl', $row['document']); // stemmed 'title'
	}
}
