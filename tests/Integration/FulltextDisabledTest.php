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
		$typeId = $this->createTestType('test-page', 'page');
		$this->createTestNode([
			'uid' => 'fulltext-disabled-node',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Fulltext Disabled']],
			],
		]);

		$nodes = iterator_to_array(
			$this->createFinder()->nodes->types('test-page'),
		);

		$this->assertCount(1, $nodes);
		$this->assertSame('fulltext-disabled-node', $nodes[0]->uid());
	}
}
