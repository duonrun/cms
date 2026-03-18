<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Types;
use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Core\Exception\HttpBadRequest;
use Exception;
use RuntimeException;

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
		$typeId = $this->createTestType('renderable-test-page');
		$this->createTestNode([
			'uid' => 'finder-renderable-node',
			'type' => $typeId,
		]);

		$finder = $this->createCms();
		$renderable = iterator_to_array($finder->nodes()
			->types('renderable-test-page', 'test-article')
			->published(null)
			->filter('renderable = true'));
		$notRenderable = iterator_to_array($finder->nodes()
			->types('test-article')
			->published(null)
			->filter('renderable = false'));

		$this->assertNotEmpty($renderable);
		$this->assertNotEmpty($notRenderable);
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

	public function testCmsNodeShortcutReturnsFilteredNodes(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		$this->createTestNode([
			'uid' => 'cms-node-shortcut-b',
			'type' => $typeId,
		]);
		$this->createTestNode([
			'uid' => 'cms-node-shortcut-a',
			'type' => $typeId,
		]);

		$cms = $this->createCmsWithDebug(false);
		$nodes = $cms->node(
			"uid ~~ 'cms-node-shortcut-%'",
			types: ['ordered-test-page'],
			limit: 1,
			order: 'uid ASC',
		);

		$this->assertCount(1, $nodes);
		$this->assertSame('cms-node-shortcut-a', Factory::meta($nodes[0], 'uid'));
	}

	public function testFinderAppliesOffset(): void
	{
		$typeId = $this->createTestType('limit-test-page');

		for ($i = 1; $i <= 5; $i++) {
			$this->createTestNode([
				'uid' => "offset-node-{$i}",
				'type' => $typeId,
			]);
		}

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('limit-test-page')
			->order('uid ASC')
			->limit(2)
			->offset(2));

		$this->assertCount(2, $nodes);
		$this->assertEquals('offset-node-3', Factory::meta($nodes[0], 'uid'));
		$this->assertEquals('offset-node-4', Factory::meta($nodes[1], 'uid'));
	}

	public function testFinderCountIgnoresOffsetAndLimit(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		for ($i = 1; $i <= 4; $i++) {
			$this->createTestNode([
				'uid' => "count-node-{$i}",
				'type' => $typeId,
			]);
		}

		$finder = $this->createCms()->nodes()
			->types('ordered-test-page')
			->order('uid ASC')
			->limit(1)
			->offset(2);

		$this->assertSame(4, $finder->count());
		$this->assertCount(1, iterator_to_array($finder));
	}

	public function testFinderCountDoesNotConsumeIteration(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		for ($i = 1; $i <= 3; $i++) {
			$this->createTestNode([
				'uid' => "count-iteration-node-{$i}",
				'type' => $typeId,
			]);
		}

		$finder = $this->createCms()->nodes()
			->types('ordered-test-page')
			->order('uid ASC');
		$nodes = iterator_to_array($finder);

		$this->assertSame(3, $finder->count());
		$this->assertSame('count-iteration-node-1', Factory::meta($nodes[0], 'uid'));
	}

	public function testFinderRewindFailsAfterGeneratorHasRun(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		for ($i = 1; $i <= 2; $i++) {
			$this->createTestNode([
				'uid' => "rewind-node-{$i}",
				'type' => $typeId,
			]);
		}

		$finder = $this->createCms()->nodes()
			->types('ordered-test-page')
			->order('uid ASC');

		$finder->rewind();
		$this->assertSame('rewind-node-1', Factory::meta($finder->current(), 'uid'));

		$finder->next();
		$this->assertSame('rewind-node-2', Factory::meta($finder->current(), 'uid'));

		$this->expectException(
			Exception::class,
		);
		$this->expectExceptionMessage('Cannot rewind a generator that was already run');
		$finder->rewind();
	}

	public function testFinderMutationAfterIterationStartsDoesNotReplaceFetchedResult(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		$this->createTestNode([
			'uid' => 'mutation-node-a',
			'type' => $typeId,
		]);
		$this->createTestNode([
			'uid' => 'mutation-node-b',
			'type' => $typeId,
		]);

		$finder = $this->createCms()->nodes()
			->types('ordered-test-page')
			->order('uid ASC');

		$finder->rewind();
		$this->assertSame('mutation-node-a', Factory::meta($finder->current(), 'uid'));

		$finder->limit(1);

		$this->assertSame(
			['mutation-node-a', 'mutation-node-b'],
			array_map(
				static fn(object $node): string => (string) Factory::meta($node, 'uid'),
				iterator_to_array($finder),
			),
		);
	}

	public function testFinderSearchesAcrossFields(): void
	{
		$typeId = $this->createTestType('ordered-test-page');

		$this->createTestNode([
			'uid' => 'search-node-alpha',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Alpha story']],
			],
		]);
		$this->createTestNode([
			'uid' => 'search-node-beta',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Beta story']],
			],
		]);

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('ordered-test-page')
			->order('uid ASC')
			->search('search ALPHA', ['uid', 'title']));

		$this->assertCount(1, $nodes);
		$this->assertSame('search-node-alpha', Factory::meta($nodes[0], 'uid'));
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

	public function testFinderRootsReturnsOnlyRootNodes(): void
	{
		$typeId = $this->createTestType('nested-test-page');

		$this->createTestNode([
			'uid' => 'roots-root-a',
			'type' => $typeId,
		]);
		$parentId = $this->createTestNode([
			'uid' => 'roots-parent',
			'type' => $typeId,
		]);
		$this->createTestNode([
			'uid' => 'roots-child',
			'type' => $typeId,
			'parent' => $parentId,
		]);

		$finder = $this->createCms();
		$nodes = iterator_to_array($finder->nodes()
			->types('nested-test-page')
			->published(null)
			->roots()
			->order('uid ASC'));

		$uids = [];

		foreach ($nodes as $node) {
			$uids[] = (string) Factory::meta($node, 'uid');
		}

		$this->assertContains('roots-parent', $uids);
		$this->assertContains('roots-root-a', $uids);
		$this->assertNotContains('roots-child', $uids);
	}

	public function testFinderChildrenOfReturnsOnlyDirectChildren(): void
	{
		$typeId = $this->createTestType('nested-test-page');

		$parentId = $this->createTestNode([
			'uid' => 'children-parent',
			'type' => $typeId,
		]);
		$this->createTestNode([
			'uid' => 'children-a',
			'type' => $typeId,
			'parent' => $parentId,
		]);
		$childParentId = $this->createTestNode([
			'uid' => 'children-b',
			'type' => $typeId,
			'parent' => $parentId,
		]);
		$this->createTestNode([
			'uid' => 'children-grandchild',
			'type' => $typeId,
			'parent' => $childParentId,
		]);

		$finder = $this->createCms();
		$directChildren = iterator_to_array($finder->nodes()
			->types('nested-test-page')
			->published(null)
			->childrenOf('children-parent')
			->order('uid ASC'));

		$uids = [];

		foreach ($directChildren as $node) {
			$uids[] = (string) Factory::meta($node, 'uid');
		}

		$this->assertSame(['children-a', 'children-b'], $uids);

		$filtered = iterator_to_array($finder->nodes()
			->types('nested-test-page')
			->published(null)
			->filter("parent = 'children-parent'")
			->order('uid ASC'));

		$filteredUids = [];

		foreach ($filtered as $node) {
			$filteredUids[] = (string) Factory::meta($node, 'uid');
		}

		$this->assertSame(['children-a', 'children-b'], $filteredUids);
	}

	public function testFinderChildrenOfAnyReturnsChildrenForMultipleParents(): void
	{
		$typeId = $this->createTestType('nested-test-page');

		$parentA = $this->createTestNode([
			'uid' => 'children-any-parent-a',
			'type' => $typeId,
		]);
		$parentB = $this->createTestNode([
			'uid' => 'children-any-parent-b',
			'type' => $typeId,
		]);

		$this->createTestNode([
			'uid' => 'children-any-a1',
			'type' => $typeId,
			'parent' => $parentA,
		]);
		$this->createTestNode([
			'uid' => 'children-any-b1',
			'type' => $typeId,
			'parent' => $parentB,
		]);
		$this->createTestNode([
			'uid' => 'children-any-unrelated',
			'type' => $typeId,
		]);

		$nodes = iterator_to_array($this->createCms()->nodes()
			->types('nested-test-page')
			->published(null)
			->childrenOfAny(['children-any-parent-a', 'children-any-parent-b'])
			->order('uid ASC'));

		$this->assertSame(
			['children-any-a1', 'children-any-b1'],
			array_map(
				static fn(object $node): string => (string) Factory::meta($node, 'uid'),
				$nodes,
			),
		);
	}

	public function testNodeChildrenReturnsDirectChildren(): void
	{
		$typeId = $this->createTestType('nested-test-page');

		$parentId = $this->createTestNode([
			'uid' => 'node-children-parent',
			'type' => $typeId,
		]);
		$childA = $this->createTestNode([
			'uid' => 'node-children-a',
			'type' => $typeId,
			'parent' => $parentId,
		]);
		$this->createTestNode([
			'uid' => 'node-children-b',
			'type' => $typeId,
			'parent' => $parentId,
			'published' => false,
		]);
		$this->createTestNode([
			'uid' => 'node-children-c',
			'type' => $typeId,
			'parent' => $childA,
		]);

		$cms = $this->createCmsWithDebug(false);
		$parent = $cms->node->byUid('node-children-parent', published: null);

		$this->assertNotNull($parent);

		$children = iterator_to_array($parent->children()->order('uid ASC'));

		$uids = [];

		foreach ($children as $child) {
			$uids[] = (string) Factory::meta($child, 'uid');
		}

		$this->assertSame(['node-children-a', 'node-children-b'], $uids);
	}

	public function testCmsNodeFinderByUidRespectsVisibilityDefaults(): void
	{
		$typeId = $this->createTestType('unpublished-test-page');

		$this->createTestNode([
			'uid' => 'finder-by-uid-visible',
			'type' => $typeId,
			'published' => true,
		]);
		$this->createTestNode([
			'uid' => 'finder-by-uid-hidden-draft',
			'type' => $typeId,
			'published' => false,
		]);

		$cms = $this->createCmsWithDebug(false);
		$visibleNode = $cms->node->byUid('finder-by-uid-visible');
		$draftNode = $cms->node->byUid('finder-by-uid-hidden-draft', published: null);

		$this->assertNotNull($visibleNode);
		$this->assertSame('finder-by-uid-visible', Factory::meta($visibleNode, 'uid'));
		$this->assertNull($cms->node->byUid('finder-by-uid-hidden-draft'));
		$this->assertNotNull($draftNode);
		$this->assertSame('finder-by-uid-hidden-draft', Factory::meta($draftNode, 'uid'));
	}

	public function testCmsNodeFinderByPathResolvesStoredPath(): void
	{
		$typeId = $this->createTestType('routing-test-page');
		$nodeId = $this->createTestNode([
			'uid' => 'finder-by-path-node',
			'type' => $typeId,
		]);
		$this->createTestPath($nodeId, '/finder-by-path-node');

		$cms = $this->createCms();
		$node = $cms->node->byPath('/finder-by-path-node');

		$this->assertNotNull($node);
		$this->assertSame('finder-by-path-node', Factory::meta($node, 'uid'));
		$this->assertNull($cms->node->byPath('/missing-path'));
	}

	public function testCmsMagicGetReturnsFinderHelpers(): void
	{
		$cms = $this->createCms();

		$this->assertInstanceOf(\Duon\Cms\Finder\Nodes::class, $cms->nodes);
		$this->assertInstanceOf(\Duon\Cms\Finder\Node::class, $cms->node);
	}

	public function testCmsRenderReturnsRenderedNodeMarkup(): void
	{
		$typeId = $this->createTestType('renderable-test-page');
		$this->createTestNode([
			'uid' => 'render-success-node',
			'type' => $typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Rendered Title']],
			],
		]);

		$cms = $this->createCmsWithDebug(false);

		$this->assertSame(
			'test-page:Rendered Title',
			(string) $cms->render('render-success-node'),
		);
	}

	public function testCmsRenderWrapsRendererFailureWhenDebugIsDisabled(): void
	{
		$typeId = $this->createTestType('renderable-test-page');
		$this->createTestNode([
			'uid' => 'render-failure-node',
			'type' => $typeId,
		]);

		$cms = $this->createCmsWithDebug(false);

		$this->expectException(HttpBadRequest::class);
		(string) $cms->render('render-failure-node', ['fail' => true]);
	}

	public function testCmsRenderRethrowsRendererFailureWhenDebugIsEnabled(): void
	{
		$typeId = $this->createTestType('renderable-test-page');
		$this->createTestNode([
			'uid' => 'render-debug-node',
			'type' => $typeId,
		]);

		$cms = $this->createCmsWithDebug(true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Forced renderer failure');
		(string) $cms->render('render-debug-node', ['fail' => true]);
	}

	public function testNodeChildrenAppliesDslQuery(): void
	{
		$typeId = $this->createTestType('nested-test-page');

		$parentId = $this->createTestNode([
			'uid' => 'node-dsl-parent',
			'type' => $typeId,
		]);
		$this->createTestNode([
			'uid' => 'node-dsl-alpha',
			'type' => $typeId,
			'parent' => $parentId,
			'published' => true,
		]);
		$this->createTestNode([
			'uid' => 'node-dsl-beta',
			'type' => $typeId,
			'parent' => $parentId,
			'published' => false,
		]);
		$this->createTestNode([
			'uid' => 'node-other-gamma',
			'type' => $typeId,
			'parent' => $parentId,
			'published' => true,
		]);

		$cms = $this->createCms();
		$parent = $cms->node->byUid('node-dsl-parent', published: null);

		$this->assertNotNull($parent);

		$children = iterator_to_array($parent
			->children("published = true & uid ~~ 'node-dsl-%'")
			->order('uid ASC'));

		$this->assertCount(1, $children);
		$this->assertSame('node-dsl-alpha', Factory::meta($children[0], 'uid'));
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

	private function createCmsWithDebug(bool $debug): Cms
	{
		$request = $this->request();
		$request->set('defaultLocale', $request->get('locales')->getDefault());

		return new Cms(
			new Context(
				$this->db(),
				$request,
				$this->config(debug: $debug),
				$this->container(),
				$this->factory(),
			),
			new Types(),
		);
	}
}
