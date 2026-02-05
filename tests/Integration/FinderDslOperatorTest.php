<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;

final class FinderDslOperatorTest extends IntegrationTestCase
{
	private string $typeHandle;

	protected function setUp(): void
	{
		parent::setUp();

		$this->typeHandle = 'dsl-test-page';
		$typeId = $this->createTestType($this->typeHandle, 'page');

		$this->createTestNode([
			'uid' => 'dsl-alpha',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => 'Alpha'],
				'slug' => ['type' => 'text', 'value' => 'alpha-1'],
				'views' => ['type' => 'number', 'value' => 10],
				'featured' => ['type' => 'boolean', 'value' => true],
				'category' => ['type' => 'text', 'value' => 'news'],
				'subtitle' => ['type' => 'text', 'value' => 'Alpha Subtitle'],
			],
		]);

		$this->createTestNode([
			'uid' => 'dsl-beta',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => 'beta'],
				'slug' => ['type' => 'text', 'value' => 'beta-2'],
				'views' => ['type' => 'number', 'value' => 3],
				'featured' => ['type' => 'boolean', 'value' => false],
				'category' => ['type' => 'text', 'value' => 'blog'],
			],
		]);

		$this->createTestNode([
			'uid' => 'dsl-gamma',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => 'GAMMA'],
				'slug' => ['type' => 'text', 'value' => 'gamma-3'],
				'views' => ['type' => 'number', 'value' => 0],
				'featured' => ['type' => 'boolean', 'value' => true],
				'category' => ['type' => 'text', 'value' => 'events'],
			],
		]);
	}

	public function testComparisonOperators(): void
	{
		$this->assertUids(['dsl-alpha'], 'title = "Alpha"');
		$this->assertUids(['dsl-beta', 'dsl-gamma'], 'title != "Alpha"');
		$this->assertUids(['dsl-alpha'], 'views > 3');
		$this->assertUids(['dsl-alpha', 'dsl-beta'], 'views >= 3');
		$this->assertUids(['dsl-gamma'], 'views < 1');
		$this->assertUids(['dsl-beta', 'dsl-gamma'], 'views <= 3');
		$this->assertUids(['dsl-alpha', 'dsl-gamma'], 'featured = true');
		$this->assertUids(['dsl-beta'], 'featured = false');
	}

	public function testLikeOperators(): void
	{
		$this->assertUids(['dsl-alpha'], 'slug ~~ "%alpha%"');
		$this->assertUids(['dsl-alpha'], 'slug ~~* "%ALPHA%"');
	}

	public function testRegexOperators(): void
	{
		$this->assertUids(['dsl-alpha'], 'slug ~ /^alpha/');
		$this->assertUids(['dsl-alpha'], 'slug ~* /^ALPHA/');
		$this->assertUids(['dsl-beta', 'dsl-gamma'], 'slug !~ /^alpha/');
		$this->assertUids(['dsl-beta', 'dsl-gamma'], 'slug !~* /^ALPHA/');
	}

	public function testMembershipOperators(): void
	{
		$this->assertUids(['dsl-alpha', 'dsl-gamma'], 'category @ ["news", "events"]');
		$this->assertUids(['dsl-beta', 'dsl-gamma'], 'category !@ ["news"]');
		$this->assertUids(['dsl-alpha', 'dsl-gamma'], 'views @ [0, 10]');
	}

	public function testExistsAndLogicalOperators(): void
	{
		$this->assertUids(['dsl-alpha'], 'subtitle');
		$this->assertUids(['dsl-alpha'], 'title = "Alpha" & views > 5');
		$this->assertUids(['dsl-alpha', 'dsl-beta'], 'title = "Alpha" | title = "beta"');
		$this->assertUids(
			['dsl-alpha', 'dsl-gamma'],
			'featured = true & (views > 5 | title = "GAMMA")',
		);
	}

	/**
	 * @param list<string> $expected
	 */
	private function assertUids(array $expected, string $query): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array(
			$finder->nodes
				->filter($query)
				->types($this->typeHandle),
		);
		$uids = array_map(static fn($node): string => $node->uid(), $nodes);
		sort($uids);
		sort($expected);

		$this->assertSame($expected, $uids, $query);
	}
}
