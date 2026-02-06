<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Cms\Tests\Support\TestDbConfig;

final class QueryPlanTest extends IntegrationTestCase
{
	public function testSqliteQueryPlanUsesVisibilityIndex(): void
	{
		if (TestDbConfig::driver() !== 'sqlite') {
			$this->markTestSkipped('SQLite query plan guard only runs on sqlite.');
		}

		$typeId = $this->createTestType('plan-test-page', 'page');
		$this->createTestNode([
			'uid' => 'plan-test-node',
			'type' => $typeId,
			'published' => true,
			'hidden' => false,
			'content' => ['title' => ['type' => 'text', 'value' => 'Plan test']],
		]);

		$table = $this->table('nodes');
		$rows = $this->db()->execute(
			"EXPLAIN QUERY PLAN SELECT node FROM {$table} INDEXED BY ix_nodes_visibility "
				. 'WHERE deleted IS NULL AND published = 1 AND hidden = 0 AND type = :type',
			['type' => $typeId],
		)->all();
		$details = array_filter(array_column($rows, 'detail'));

		$this->assertNotEmpty($details);
		$this->assertTrue(
			$this->detailsContainIndex($details, 'ix_nodes_visibility'),
			'Expected sqlite query plan to use ix_nodes_visibility',
		);
	}

	public function testPgsqlExplainRuns(): void
	{
		if (TestDbConfig::driver() !== 'pgsql') {
			$this->markTestSkipped('PostgreSQL query plan check only runs on pgsql.');
		}

		$typeId = $this->createTestType('plan-test-page', 'page');
		$this->createTestNode([
			'uid' => 'plan-test-node',
			'type' => $typeId,
			'published' => true,
			'hidden' => false,
			'content' => ['title' => ['type' => 'text', 'value' => 'Plan test']],
		]);

		$table = $this->table('nodes');
		$rows = $this->db()->execute(
			"EXPLAIN SELECT node FROM {$table} "
				. 'WHERE deleted IS NULL AND published = true AND hidden = false AND type = :type',
			['type' => $typeId],
		)->all();

		$this->assertNotEmpty($rows);
	}

	/**
	 * @param list<string> $details
	 */
	private function detailsContainIndex(array $details, string $indexName): bool
	{
		foreach ($details as $detail) {
			if (str_contains($detail, $indexName)) {
				return true;
			}
		}

		return false;
	}
}
