<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Locales;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Serializer;
use Duon\Cms\Node\Types;
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
	private \Duon\Cms\Cms $cms;
	private Factory $factory;
	private Types $types;

	protected function setUp(): void
	{
		parent::setUp();
		$this->types = new Types();

		$this->context = $this->createContext();
		$this->cms = $this->createStub(\Duon\Cms\Cms::class);
		$this->factory = new Factory($this->container(), types: $this->types);
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
			$this->container(),
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

		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, $data);

		$this->assertInstanceOf(PlainPage::class, $node);
	}

	public function testCreatePlainBlock(): void
	{
		$node = $this->factory->create(PlainBlock::class, $this->context, $this->cms, [
			'uid' => 'plain-block-1',
			'content' => [],
		]);

		$this->assertInstanceOf(PlainBlock::class, $node);
	}

	public function testCreateTestPage(): void
	{
		$node = $this->factory->create(TestPage::class, $this->context, $this->cms, [
			'uid' => 'test-page-1',
			'content' => [],
		]);

		$this->assertInstanceOf(TestPage::class, $node);
	}

	// -- Field hydration ------------------------------------------------------

	public function testPlainPageFieldsAreHydrated(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'hydrated-1',
			'content' => [
				'heading' => ['value' => ['en' => 'Test Heading']],
			],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$this->assertContains('heading', $fieldNames);
		$this->assertContains('body', $fieldNames);
		$this->assertCount(2, $fieldNames);
	}

	public function testPlainPageTitleResolution(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'titled-1',
			'content' => [
				'heading' => ['value' => ['en' => 'My Title']],
			],
		]);

		$serializer = new Serializer($this->factory->hydrator(), $this->types);
		$title = $serializer->resolveTitle($node);
		$this->assertEquals('My Title', $title);
	}

	public function testPlainPageTitleReturnsEmptyWhenNoContent(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'untitled-1',
			'content' => [],
		]);

		$serializer = new Serializer($this->factory->hydrator(), $this->types);
		$title = $serializer->resolveTitle($node);
		$this->assertSame('', $title);
	}

	// -- WeakMap data storage -------------------------------------------------

	public function testDataForReturnsRawData(): void
	{
		$data = [
			'uid' => 'data-1',
			'handle' => 'plain-page',
			'content' => ['heading' => ['value' => ['en' => 'Hello']]],
		];

		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, $data);
		$stored = Factory::dataFor($node);

		$this->assertEquals('data-1', $stored['uid']);
		$this->assertEquals('plain-page', $stored['handle']);
	}

	public function testFieldNamesForReturnsFieldNames(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'fields-1',
			'content' => [],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$this->assertEquals(['heading', 'body'], $fieldNames);
	}

	public function testMetaReturnsDataValue(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'meta-1',
			'handle' => 'plain-page',
			'published' => true,
			'content' => [],
		]);

		$this->assertEquals('meta-1', Factory::meta($node, 'uid'));
		$this->assertEquals('plain-page', Factory::meta($node, 'handle'));
		$this->assertTrue(Factory::meta($node, 'published'));
		$this->assertNull(Factory::meta($node, 'nonexistent'));
	}

	public function testDataForUnknownNodeReturnsEmpty(): void
	{
		$unknown = new stdClass();
		$this->assertEmpty(Factory::dataFor($unknown));
		$this->assertEmpty(Factory::fieldNamesFor($unknown));
	}

	// -- HasInit callback -----------------------------------------------------

	public function testHasInitIsCalledForPlainObject(): void
	{
		$node = $this->factory->create(PlainPageWithInit::class, $this->context, $this->cms, [
			'uid' => 'init-1',
			'content' => [],
		]);

		$this->assertInstanceOf(PlainPageWithInit::class, $node);
		$this->assertTrue($node->initialized);
	}

	// -- Blueprint creation ---------------------------------------------------

	public function testBlueprintCreatesEmptyPlainNode(): void
	{
		$node = $this->factory->blueprint(PlainPage::class, $this->context, $this->cms);

		$this->assertInstanceOf(PlainPage::class, $node);
		$fieldNames = Factory::fieldNamesFor($node);
		$this->assertCount(2, $fieldNames);
	}

	// -- NodeMeta with plain objects ------------------------------------------

	public function testNodeMetaRoutableForPlainPage(): void
	{
		$this->assertTrue($this->types->routable(PlainPage::class));
	}

	public function testNodeMetaRenderableForPlainBlock(): void
	{
		$this->assertTrue($this->types->renderable(PlainBlock::class));
	}

	public function testNodeMetaIsNodeForPlainPage(): void
	{
		$this->assertTrue($this->types->isNode(PlainPage::class));
	}

	public function testNodeMetaHandleForPlainPage(): void
	{
		$this->assertEquals('plain-page', $this->types->handle(PlainPage::class));
	}

	public function testNodeMetaLabelForPlainPage(): void
	{
		$this->assertEquals('Plain Page', $this->types->label(PlainPage::class));
	}

	public function testNodeMetaTitleFieldForPlainPage(): void
	{
		$this->assertEquals('heading', $this->types->titleField(PlainPage::class));
	}

	public function testNodeMetaFieldOrderForPlainPage(): void
	{
		$this->assertEquals(['heading', 'body'], $this->types->fieldOrder(PlainPage::class));
	}

	public function testNodeMetaDeletableForPlainBlock(): void
	{
		$this->assertFalse($this->types->deletable(PlainBlock::class));
	}

	public function testNodeMetaDeletableDefaultsToTrue(): void
	{
		$this->assertTrue($this->types->deletable(PlainPage::class));
	}

	// -- Serializer with plain objects ------------------------------------

	public function testSerializerFieldsForPlainPage(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'ser-1',
			'content' => [],
		]);

		$serializer = new Serializer($this->factory->hydrator(), $this->types);
		$fieldNames = Factory::fieldNamesFor($node);
		$fields = $serializer->fields($node, $fieldNames);

		$this->assertCount(2, $fields);
		$this->assertEquals('heading', $fields[0]['name']);
		$this->assertEquals('body', $fields[1]['name']);
	}

	public function testSerializerBlueprintForPlainPage(): void
	{
		$node = $this->factory->blueprint(PlainPage::class, $this->context, $this->cms);
		$fieldNames = Factory::fieldNamesFor($node);
		$locales = $this->context->locales();

		$serializer = new Serializer($this->factory->hydrator(), $this->types);
		$blueprint = $serializer->blueprint($node, $fieldNames, $locales);

		$this->assertArrayHasKey('uid', $blueprint);
		$this->assertArrayHasKey('content', $blueprint);
		$this->assertArrayHasKey('fields', $blueprint);
		$this->assertArrayHasKey('type', $blueprint);
		$this->assertTrue($blueprint['type']['routable']);
		$this->assertTrue($blueprint['type']['renderable']);
		$this->assertEquals('plain-page', $blueprint['type']['handle']);
		$this->assertEquals(PlainPage::class, $blueprint['type']['class']);
		$this->assertArrayHasKey('route', $blueprint);
		$this->assertFalse($blueprint['published']);
	}

	// -- Node wrapper with plain objects --------------------------------------

	public function testNodeFieldAccess(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'proxy-1',
			'content' => [
				'heading' => ['value' => ['en' => 'Proxy Title']],
			],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$proxy = new Node($node, $fieldNames, $this->factory->hydrator(), $this->types);

		$this->assertTrue(isset($proxy->heading));
		$this->assertEquals('Proxy Title', (string) $proxy->heading);
	}

	public function testNodeMethodDelegation(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'proxy-2',
			'content' => [
				'heading' => ['value' => ['en' => 'Method Test']],
			],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$proxy = new Node($node, $fieldNames, $this->factory->hydrator(), $this->types);

		$this->assertEquals('Method Test', $proxy->title());
	}

	public function testNodeMetaPropertyProvidesNodeData(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'proxy-meta-1',
			'published' => true,
			'content' => [],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$proxy = new Node($node, $fieldNames, $this->factory->hydrator(), $this->types);

		$this->assertEquals('proxy-meta-1', $proxy->meta->uid);
		$this->assertTrue($proxy->meta->published);
		$this->assertEquals('Plain Page', $proxy->meta->name);
	}

	public function testNodeMetaMethodCompatibility(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'proxy-meta-2',
			'published' => true,
			'content' => [],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$proxy = new Node($node, $fieldNames, $this->factory->hydrator(), $this->types);

		$this->assertSame('proxy-meta-2', $proxy->meta('uid'));
		$this->assertSame('fallback', $proxy->meta('missing', 'fallback'));
	}

	public function testNodeUnsetFieldReturnsNull(): void
	{
		$node = $this->factory->create(PlainPage::class, $this->context, $this->cms, [
			'uid' => 'proxy-3',
			'content' => [],
		]);

		$fieldNames = Factory::fieldNamesFor($node);
		$proxy = new Node($node, $fieldNames, $this->factory->hydrator(), $this->types);

		$this->assertNull($proxy->heading);
		$this->assertFalse(isset($proxy->heading));
	}
}
