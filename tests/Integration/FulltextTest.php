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
	// =========================================================================
	// PostgreSQL Tests
	// =========================================================================

	public function testPgsqlCleanRemovesOrphanedEntries(): void
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

	public function testPgsqlNodesQueryReturnsPublishedNodes(): void
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

	public function testPgsqlUpsertCreatesFulltextEntry(): void
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

	// =========================================================================
	// SQLite Tests
	// =========================================================================

	public function testSqliteFts5TableExists(): void
	{
		if ($this->db()->getPdoDriver() !== 'sqlite') {
			$this->markTestSkipped('FTS5 test requires SQLite');
		}

		$db = $this->db();

		// Check that the FTS5 virtual table exists
		$result = $db->execute(
			"SELECT name FROM sqlite_master WHERE type='table' AND name='cms_fulltext_fts'",
		)->one();

		$this->assertNotNull($result);
		$this->assertEquals('cms_fulltext_fts', $result['name']);
	}

	public function testSqliteFulltextIndexMapping(): void
	{
		if ($this->db()->getPdoDriver() !== 'sqlite') {
			$this->markTestSkipped('FTS5 test requires SQLite');
		}

		$db = $this->db();
		$typeId = $this->createTestType('sqlite-fts-test', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'sqlite-fts-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'SQLite FTS Test'],
		]);

		// Insert into the index mapping table
		$result = $db->fulltext->upsertIdx([
			'node' => $nodeId,
			'locale' => 'en',
		])->one();

		$this->assertNotNull($result);
		$this->assertArrayHasKey('rowid', $result);
		$rowid = $result['rowid'];

		// Verify the mapping exists
		$mapping = $db->execute(
			'SELECT * FROM cms_fulltext_idx WHERE node = :node AND locale = :locale',
			['node' => $nodeId, 'locale' => 'en'],
		)->one();

		$this->assertNotNull($mapping);
		$this->assertEquals($rowid, $mapping['rowid']);
	}

	public function testSqliteFts5InsertAndSearch(): void
	{
		if ($this->db()->getPdoDriver() !== 'sqlite') {
			$this->markTestSkipped('FTS5 test requires SQLite');
		}

		$db = $this->db();
		$typeId = $this->createTestType('sqlite-fts-search', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'sqlite-fts-search-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'Brewery Tour'],
		]);

		// Create index mapping and get rowid
		$result = $db->fulltext->upsertIdx([
			'node' => $nodeId,
			'locale' => 'en',
		])->one();
		$rowid = $result['rowid'];

		// Insert into FTS5 table (delete first in case of update)
		$db->fulltext->deleteFts(['rowid' => $rowid])->run();
		$db->fulltext->upsert([
			'rowid' => $rowid,
			'document' => 'Brewery tour with craft beer tasting and hop garden visit',
		])->run();

		// Search for "beer"
		$results = $db->fulltext->search(['query' => 'beer'])->all();

		$this->assertNotEmpty($results);
		$this->assertEquals($nodeId, $results[0]['node']);
		$this->assertEquals('en', $results[0]['locale']);
	}

	public function testSqliteCleanRemovesOrphanedFtsEntries(): void
	{
		if ($this->db()->getPdoDriver() !== 'sqlite') {
			$this->markTestSkipped('FTS5 clean test requires SQLite');
		}

		$db = $this->db();
		$typeId = $this->createTestType('sqlite-fts-clean', 'page');
		$nodeId = $this->createTestNode([
			'uid' => 'sqlite-fts-clean-node',
			'type' => $typeId,
			'published' => true,
			'content' => ['title' => 'Clean Test'],
		]);

		// Create index mapping and FTS entry
		$result = $db->fulltext->upsertIdx([
			'node' => $nodeId,
			'locale' => 'en',
		])->one();
		$rowid = $result['rowid'];

		$db->fulltext->deleteFts(['rowid' => $rowid])->run();
		$db->fulltext->upsert([
			'rowid' => $rowid,
			'document' => 'Test document for cleaning',
		])->run();

		// Verify entries exist
		$idxCount = $db->execute(
			'SELECT COUNT(*) as cnt FROM cms_fulltext_idx WHERE node = :node',
			['node' => $nodeId],
		)->one();
		$this->assertEquals(1, $idxCount['cnt']);

		// Unpublish the node
		$db->execute(
			'UPDATE cms_nodes SET published = 0 WHERE node = :node',
			['node' => $nodeId],
		)->run();

		// Run clean (FTS5, index, and legacy tables)
		$db->fulltext->cleanFts()->run();
		$db->fulltext->cleanIdx()->run();
		$db->fulltext->clean()->run();

		// Verify index entry was removed
		$idxCount = $db->execute(
			'SELECT COUNT(*) as cnt FROM cms_fulltext_idx WHERE node = :node',
			['node' => $nodeId],
		)->one();
		$this->assertEquals(0, $idxCount['cnt']);
	}
}
