<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Commands\Fulltext;
use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Cms\Tests\Support\TestDbConfig;

final class FulltextCommandTest extends IntegrationTestCase
{
	protected bool $useTransactions = false;

	public function testFulltextCommandRunsOnPgsql(): void
	{
		if (TestDbConfig::driver() !== 'pgsql') {
			$this->markTestSkipped('Fulltext command is only validated on pgsql');
		}

		$table = $this->table('fulltext');
		$this->assertTrue($this->tableExists($table));

		$command = new Fulltext(
			TestDbConfig::connection(),
			['config' => $this->config(['db.features.fulltext.enabled' => true])],
		);

		$this->assertSame(0, $command->run());
		$this->db()->execute("DELETE FROM {$table}")->run();
	}

	public function testFulltextCommandIndexesSqliteContent(): void
	{
		if (TestDbConfig::driver() !== 'sqlite') {
			$this->markTestSkipped('Fulltext command is only validated on sqlite');
		}

		$table = $this->table('fulltext');
		$this->assertTrue($this->tableExists($table));

		$type = $this->createTestType('fulltext-test-page');
		$publishedNode = $this->createTestNode([
			'uid' => 'fulltext-sqlite-published',
			'type' => $type,
			'published' => 1,
			'content' => [
				'title' => [
					'type' => 'text',
					'value' => ['en' => 'SQLite fulltext needle'],
				],
				'body' => [
					'type' => 'html',
					'value' => ['en' => '<p>sqliteneedle</p>'],
				],
			],
		]);
		$unpublishedNode = $this->createTestNode([
			'uid' => 'fulltext-sqlite-unpublished',
			'type' => $type,
			'published' => 0,
			'content' => [
				'title' => [
					'type' => 'text',
					'value' => ['en' => 'SQLite fulltext hidden'],
				],
				'body' => [
					'type' => 'html',
					'value' => ['en' => '<p>sqlitehidden</p>'],
				],
			],
		]);

		$command = new Fulltext(
			TestDbConfig::connection(),
			['config' => $this->config(['db.features.fulltext.enabled' => true])],
		);

		$this->assertSame(0, $command->run());

		$matched = $this->db()->execute(
			"SELECT node FROM {$table} WHERE {$table} MATCH :query",
			['query' => 'sqliteneedle'],
		)->all();
		$matchedIds = array_map(static fn(array $row): int => (int) $row['node'], $matched);
		$this->assertContains($publishedNode, $matchedIds);

		$hidden = $this->db()->execute(
			"SELECT node FROM {$table} WHERE {$table} MATCH :query",
			['query' => 'sqlitehidden'],
		)->all();
		$hiddenIds = array_map(static fn(array $row): int => (int) $row['node'], $hidden);
		$this->assertNotContains($unpublishedNode, $hiddenIds);
	}
}
