<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Context;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\Node;
use Duon\Cms\Tests\Fixtures\Node\TestPage;
use Duon\Cms\Tests\IntegrationTestCase;

final class FulltextSearchTest extends IntegrationTestCase
{
	public function testFulltextQueryFiltersPublishedNodes(): void
	{
		$typeHandle = 'fulltext-search-page';
		$typeId = $this->createTestType($typeHandle, 'page');

		$publishedUid = 'fulltext-search-published';
		$unpublishedUid = 'fulltext-search-unpublished';

		$this->createTestNode([
			'uid' => $publishedUid,
			'type' => $typeId,
			'published' => true,
			'content' => [
				'title' => ['type' => 'text', 'value' => 'Fulltext needle'],
				'body' => ['type' => 'html', 'value' => '<p>Fulltext needle</p>'],
			],
		]);

		$this->createTestNode([
			'uid' => $unpublishedUid,
			'type' => $typeId,
			'published' => false,
			'content' => [
				'title' => ['type' => 'text', 'value' => 'Fulltext hidden'],
				'body' => ['type' => 'html', 'value' => '<p>Fulltext hidden</p>'],
			],
		]);

		$this->db()->fulltext->clear()->run();
		$this->db()->fulltext->rebuild()->run();

		$finder = $this->createFulltextFinder($typeHandle);
		$nodes = iterator_to_array(
			$finder->nodes->filter('fulltext ~~ "needle"')->types($typeHandle),
		);
		$uids = array_map(static fn($node): string => $node->uid(), $nodes);
		sort($uids);

		$this->assertSame([$publishedUid], $uids);
	}

	private function createFulltextFinder(string $typeHandle): Finder
	{
		$registry = $this->registry();
		$registry->tag(Node::class)->add($typeHandle, TestPage::class);

		return new Finder(new Context(
			$this->db(),
			$this->request(),
			$this->config(['db.features.fulltext.enabled' => true]),
			$registry,
			$this->factory(),
		));
	}
}
