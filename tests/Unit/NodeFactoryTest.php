<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Locales;
use Duon\Cms\Node\NodeFactory;
use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Node\NodeProxy;
use Duon\Cms\Node\NodeSerializer;
use Duon\Cms\Tests\Fixtures\Node\PlainBlock;
use Duon\Cms\Tests\Fixtures\Node\PlainPage;
use Duon\Cms\Tests\Fixtures\Node\PlainPageWithInit;
use Duon\Cms\Tests\Fixtures\Node\TestPage;
use Duon\Cms\Tests\TestCase;
use Duon\Core\Request;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class NodeFactoryTest extends TestCase
{
	private Context $context;
	private \Duon\Cms\Finder\Finder $finder;
	private NodeFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		NodeMeta::clearCache();

		$this->context = $this->createContext();
		$this->finder = $this->createStub(\Duon\Cms\Finder\Finder::class);
		$this->factory = new NodeFactory($this->registry());
	}

	protected function tearDown(): void
	{
		NodeMeta::clearCache();
		parent::tearDown();
	}

	private function createContext(): Context
	{
		$psrRequest = $this->psrRequest();
		$locales = new Locales();
		$locales->add('en', title: 'English', domains: ['www.example.com']);
		$locales->add('de', title: 'Deutsch', domains: ['www.example.de'], fallback: 'en');

		$psrRequest = $psrRequest
			->withAttribute('locales', $locales)
			->withAttribute('locale', $locales->get('en'))
			->withAttribute('defaultLocale', $locales->getDefault());

		$request = new Request($psrRequest);

		return new Context(
			$this->db(),
			$request,
			$this->config(['path.prefix' => '/cms']),
			$this->registry(),
			$this->factory(),
		);
	}

	// -- Plain object creation ------------------------------------------------

	public function testCreatePlainPage(): void
	{
		$data = [
			'uid' => 'plain-page-1',
			'content' => [
				'heading' => ['value' => ['en' => 'Hello World']],
				'body' => ['value' => ['en' => 'Some body text']],
			],
		];

		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, $data);

		$this->assertInstanceOf(PlainPage::class, $node);
	}

	public function testCreatePlainBlock(): void
	{
		$node = $this->factory->create(PlainBlock::class, $this->context, $this->finder, [
			'uid' => 'plain-block-1',
			'content' => [],
		]);

		$this->assertInstanceOf(PlainBlock::class, $node);
	}

	public function testCreateTestPage(): void
	{
		$node = $this->factory->create(TestPage::class, $this->context, $this->finder, [
			'uid' => 'test-page-1',
			'content' => [],
		]);

		$this->assertInstanceOf(TestPage::class, $node);
	}

	// -- Field hydration ------------------------------------------------------

	public function testPlainPageFieldsAreHydrated(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'hydrated-1',
			'content' => [
				'heading' => ['value' => ['en' => 'Test Heading']],
			],
		]);

		$fieldNames = NodeFactory::fieldNamesFor($node);
		$this->assertContains('heading', $fieldNames);
		$this->assertContains('body', $fieldNames);
		$this->assertCount(2, $fieldNames);
	}

	public function testPlainPageTitleResolution(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'titled-1',
			'content' => [
				'heading' => ['value' => ['en' => 'My Title']],
			],
		]);

		$serializer = new NodeSerializer($this->factory->hydrator());
		$title = $serializer->resolveTitle($node);
		$this->assertEquals('My Title', $title);
	}

	public function testPlainPageTitleReturnsEmptyWhenNoContent(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'untitled-1',
			'content' => [],
		]);

		$serializer = new NodeSerializer($this->factory->hydrator());
		$title = $serializer->resolveTitle($node);
		$this->assertSame('', $title);
	}

	// -- WeakMap data storage -------------------------------------------------

	public function testDataForReturnsRawData(): void
	{
		$data = [
			'uid' => 'data-1',
			'handle' => 'plain-page',
			'kind' => 'page',
			'content' => ['heading' => ['value' => ['en' => 'Hello']]],
		];

		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, $data);
		$stored = NodeFactory::dataFor($node);

		$this->assertEquals('data-1', $stored['uid']);
		$this->assertEquals('plain-page', $stored['handle']);
		$this->assertEquals('page', $stored['kind']);
	}

	public function testFieldNamesForReturnsFieldNames(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'fields-1',
			'content' => [],
		]);

		$fieldNames = NodeFactory::fieldNamesFor($node);
		$this->assertEquals(['heading', 'body'], $fieldNames);
	}

	public function testMetaReturnsDataValue(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'meta-1',
			'handle' => 'plain-page',
			'published' => true,
			'content' => [],
		]);

		$this->assertEquals('meta-1', NodeFactory::meta($node, 'uid'));
		$this->assertEquals('plain-page', NodeFactory::meta($node, 'handle'));
		$this->assertTrue(NodeFactory::meta($node, 'published'));
		$this->assertNull(NodeFactory::meta($node, 'nonexistent'));
	}

	public function testDataForUnknownNodeReturnsEmpty(): void
	{
		$unknown = new stdClass();
		$this->assertEmpty(NodeFactory::dataFor($unknown));
		$this->assertEmpty(NodeFactory::fieldNamesFor($unknown));
	}

	// -- HasInit callback -----------------------------------------------------

	public function testHasInitIsCalledForPlainObject(): void
	{
		$node = $this->factory->create(PlainPageWithInit::class, $this->context, $this->finder, [
			'uid' => 'init-1',
			'content' => [],
		]);

		$this->assertInstanceOf(PlainPageWithInit::class, $node);
		$this->assertTrue($node->initialized);
	}

	// -- Blueprint creation ---------------------------------------------------

	public function testBlueprintCreatesEmptyPlainNode(): void
	{
		$node = $this->factory->blueprint(PlainPage::class, $this->context, $this->finder);

		$this->assertInstanceOf(PlainPage::class, $node);
		$fieldNames = NodeFactory::fieldNamesFor($node);
		$this->assertCount(2, $fieldNames);
	}

	// -- NodeMeta with plain objects ------------------------------------------

	public function testNodeMetaKindForPlainPage(): void
	{
		$this->assertEquals('page', NodeMeta::kind(PlainPage::class));
	}

	public function testNodeMetaKindForPlainBlock(): void
	{
		$this->assertEquals('block', NodeMeta::kind(PlainBlock::class));
	}

	public function testNodeMetaIsNodeForPlainPage(): void
	{
		$this->assertTrue(NodeMeta::isNode(PlainPage::class));
	}

	public function testNodeMetaHandleForPlainPage(): void
	{
		$this->assertEquals('plain-page', NodeMeta::handle(PlainPage::class));
	}

	public function testNodeMetaNameForPlainPage(): void
	{
		$this->assertEquals('Plain Page', NodeMeta::name(PlainPage::class));
	}

	public function testNodeMetaTitleFieldForPlainPage(): void
	{
		$this->assertEquals('heading', NodeMeta::titleField(PlainPage::class));
	}

	public function testNodeMetaFieldOrderForPlainPage(): void
	{
		$this->assertEquals(['heading', 'body'], NodeMeta::fieldOrder(PlainPage::class));
	}

	public function testNodeMetaDeletableForPlainBlock(): void
	{
		$this->assertFalse(NodeMeta::deletable(PlainBlock::class));
	}

	public function testNodeMetaDeletableDefaultsToTrue(): void
	{
		$this->assertTrue(NodeMeta::deletable(PlainPage::class));
	}

	// -- NodeSerializer with plain objects ------------------------------------

	public function testSerializerFieldsForPlainPage(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'ser-1',
			'content' => [],
		]);

		$serializer = new NodeSerializer($this->factory->hydrator());
		$fieldNames = NodeFactory::fieldNamesFor($node);
		$fields = $serializer->fields($node, $fieldNames);

		$this->assertCount(2, $fields);
		$this->assertEquals('heading', $fields[0]['name']);
		$this->assertEquals('body', $fields[1]['name']);
	}

	public function testSerializerBlueprintForPlainPage(): void
	{
		$node = $this->factory->blueprint(PlainPage::class, $this->context, $this->finder);
		$fieldNames = NodeFactory::fieldNamesFor($node);
		$locales = $this->context->locales();

		$serializer = new NodeSerializer($this->factory->hydrator());
		$blueprint = $serializer->blueprint($node, $fieldNames, $locales);

		$this->assertArrayHasKey('uid', $blueprint);
		$this->assertArrayHasKey('content', $blueprint);
		$this->assertArrayHasKey('fields', $blueprint);
		$this->assertArrayHasKey('type', $blueprint);
		$this->assertEquals('page', $blueprint['type']['kind']);
		$this->assertEquals('plain-page', $blueprint['type']['handle']);
		$this->assertEquals(PlainPage::class, $blueprint['type']['class']);
		$this->assertArrayHasKey('route', $blueprint);
		$this->assertFalse($blueprint['published']);
	}

	// -- NodeProxy with plain objects -----------------------------------------

	public function testNodeProxyFieldAccess(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'proxy-1',
			'content' => [
				'heading' => ['value' => ['en' => 'Proxy Title']],
			],
		]);

		$fieldNames = NodeFactory::fieldNamesFor($node);
		$proxy = new NodeProxy($node, $fieldNames, $this->factory->hydrator());

		$this->assertTrue(isset($proxy->heading));
		$this->assertEquals('Proxy Title', (string) $proxy->heading);
	}

	public function testNodeProxyMethodDelegation(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'proxy-2',
			'content' => [
				'heading' => ['value' => ['en' => 'Method Test']],
			],
		]);

		$fieldNames = NodeFactory::fieldNamesFor($node);
		$proxy = new NodeProxy($node, $fieldNames, $this->factory->hydrator());

		$this->assertEquals('Method Test', $proxy->title());
	}

	public function testNodeProxyUnsetFieldReturnsNull(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->finder, [
			'uid' => 'proxy-3',
			'content' => [],
		]);

		$fieldNames = NodeFactory::fieldNamesFor($node);
		$proxy = new NodeProxy($node, $fieldNames, $this->factory->hydrator());

		$this->assertNull($proxy->heading);
		$this->assertFalse(isset($proxy->heading));
	}
}
