<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Context;
use Duon\Cms\Tests\IntegrationTestCase;

final class FulltextDisabledTest extends IntegrationTestCase
{
	protected function createContext(): Context
	{
		return new Context(
			$this->db(),
			$this->request(),
			$this->config(['db.features.fulltext.enabled' => false]),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testFinderWorksWhenFulltextDisabled(): void
	{
		$type = $this->db()->nodes->type(['handle' => 'test-page'])->one();
		$typeId = $type ? (int) $type['type'] : $this->createTestType('test-page', 'page');
		$this->createTestNode([
			'uid' => 'fulltext-disabled-node',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Fulltext Disabled']],
			],
		]);

		$nodes = iterator_to_array(
			$this->createFinder()->nodes->filter('uid = "fulltext-disabled-node"')->types('test-page'),
		);

		$this->assertCount(1, $nodes);
		$this->assertSame('fulltext-disabled-node', $nodes[0]->uid());
	}
}
