<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Types;
use Duon\Cms\Tests\IntegrationTestCase;

final class FinderTest extends IntegrationTestCase
{
	private Types $types;

	protected function setUp(): void
	{
		parent::setUp();
		$this->types = new Types();

		$this->loadFixtures('basic-types', 'sample-nodes');
	}

	public function testFinderReturnsNodesOfSpecificType(): void
	{
		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes->types('test-article'));

		$this->assertGreaterThan(0, count($nodes));

		foreach ($nodes as $node) {
			$this->assertEquals('test-article', $this->types->get(Node::unwrap($node)::class, 'handle'));
		}
	}

	public function testFinderFiltersPublishedNodes(): void
	{
		$finder = $this->createCms();
		$publishedNodes = iterator_to_array($finder->nodes->types('test-article')->published(true));
		$allNodes = iterator_to_array($finder->nodes->types('test-article')->published(null));

		$this->assertNotEmpty($publishedNodes);
		$this->assertGreaterThan(count($publishedNodes), count($allNodes));

		foreach ($publishedNodes as $node) {
			$this->assertTrue(Factory::dataFor($node)['published']);
		}
	}

	public function testFinderFiltersUnpublishedNodes(): void
	{
		$finder = $this->createCms();
		$unpublishedNodes = iterator_to_array($finder->nodes()
			->types('test-article')
			->published(false));

		$this->assertNotEmpty($unpublishedNodes);

		foreach ($unpublishedNodes as $node) {
			$this->assertFalse(Factory::dataFor($node)['published']);
		}
	}

	public function testFinderFiltersByRoutableBuiltin(): void
	{
		$typeId = $this->createTestType('routing-test-page');
		$this->createTestNode([
			'uid' => 'finder-routable-node',
			'type' => $typeId,
		]);

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('routing-test-page', 'test-article')
			->published(null)
			->filter('routable = true'));

		$this->assertNotEmpty($nodes);

		foreach ($nodes as $node) {
			$this->assertTrue((bool) $this->types->get(Node::unwrap($node)::class, 'routable', false));
		}
	}

	public function testFinderFiltersByRenderableBuiltin(): void
	{
		$finder = $this->createCms();
		$renderable = iterator_to_array($finder->nodes()
			->types('test-article')
			->published(null)
			->filter('renderable = true'));
		$notRenderable = iterator_to_array($finder->nodes()
			->types('test-article')
			->published(null)
			->filter('renderable = false'));

		$this->assertNotEmpty($renderable);
		$this->assertEmpty($notRenderable);
	}

	public function testFinderSupportsMultipleTypes(): void
	{
		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('test-home', 'test-article'));

		$this->assertNotEmpty($nodes);

		$typeHandles = [];

		foreach ($nodes as $node) {
			$typeHandles[] = $this->types->get(Node::unwrap($node)::class, 'handle');
		}

		$uniqueTypes = array_unique($typeHandles);
		$this->assertContains('test-home', $uniqueTypes);
		$this->assertContains('test-article', $uniqueTypes);
	}

	public function testFinderOrdersByField(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		// Create nodes with specific UIDs to ensure predictable ordering
		$this->createTestNode([
			'uid' => 'ordered-c',
			'type' => $typeId,
			'content' => ['title' => ['type' => 'text', 'value' => ['en' => 'C Title']]],
		]);
		$this->createTestNode([
			'uid' => 'ordered-a',
			'type' => $typeId,
			'content' => ['title' => ['type' => 'text', 'value' => ['en' => 'A Title']]],
		]);
		$this->createTestNode([
			'uid' => 'ordered-b',
			'type' => $typeId,
			'content' => ['title' => ['type' => 'text', 'value' => ['en' => 'B Title']]],
		]);

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('ordered-test-page')
			->order('uid ASC'));

		$this->assertCount(3, $nodes);
		$this->assertEquals('ordered-a', Factory::meta($nodes[0], 'uid'));
		$this->assertEquals('ordered-b', Factory::meta($nodes[1], 'uid'));
		$this->assertEquals('ordered-c', Factory::meta($nodes[2], 'uid'));
	}

	public function testFinderLimitsResults(): void
	{
		$typeId = $this->createTestType('limit-test-page');

		for ($i = 1; $i <= 5; $i++) {
			$this->createTestNode([
				'uid' => "limit-node-{$i}",
				'type' => $typeId,
			]);
		}

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('limit-test-page')
			->limit(3));

		$this->assertCount(3, $nodes);
	}

	public function testFinderFiltersHiddenNodes(): void
	{
		$typeId = $this->createTestType('hidden-test-page');

		$this->createTestNode([
			'uid' => 'visible-node',
			'type' => $typeId,
			'hidden' => false,
		]);

		$this->createTestNode([
			'uid' => 'hidden-node',
			'type' => $typeId,
			'hidden' => true,
		]);

		$finder = $this->createCms();
		$visibleNodes = iterator_to_array($finder->nodes()
			->types('hidden-test-page')
			->hidden(false));

		$this->assertCount(1, $visibleNodes);
		$this->assertEquals('visible-node', Factory::meta($visibleNodes[0], 'uid'));
	}

	public function testFinderReturnsEmptyArrayWhenNoResults(): void
	{
		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('non-existent-type'));

		$this->assertIsArray($nodes);
		$this->assertEmpty($nodes);
	}

	public function testFinderWithFixtureData(): void
	{
		$finder = $this->createCms();
		$homepage = iterator_to_array($finder->nodes()->types('test-home'));

		$this->assertNotEmpty($homepage);

		$homepageNode = null;

		foreach ($homepage as $node) {
			if (Factory::meta($node, 'uid') === 'test-homepage') {
				$homepageNode = $node;
				break;
			}
		}

		$this->assertNotNull($homepageNode, 'test-homepage node should exist');
		$this->assertTrue(Factory::dataFor($homepageNode)['published']);

		$content = Factory::dataFor($homepageNode)['content'];
		$this->assertArrayHasKey('title', $content);
		$this->assertEquals('Testhomepage', $content['title']['value']['de']);
		$this->assertEquals('Test Homepage', $content['title']['value']['en']);
	}
}
