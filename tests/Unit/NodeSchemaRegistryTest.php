<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Node\Schema;
use Duon\Cms\Node\Schema\DeletableHandler;
use Duon\Cms\Node\Schema\FieldOrderHandler;
use Duon\Cms\Node\Schema\HandleHandler;
use Duon\Cms\Node\Schema\LabelHandler;
use Duon\Cms\Node\Schema\PermissionHandler;
use Duon\Cms\Node\Schema\Registry;
use Duon\Cms\Node\Schema\RenderHandler;
use Duon\Cms\Node\Schema\RouteHandler;
use Duon\Cms\Node\Schema\TitleHandler;
use Duon\Cms\Node\Types;
use Duon\Cms\Schema\Deletable;
use Duon\Cms\Schema\FieldOrder;
use Duon\Cms\Schema\Handle;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Permission;
use Duon\Cms\Schema\Render;
use Duon\Cms\Schema\Route;
use Duon\Cms\Schema\Title;
use Duon\Cms\Tests\Fixtures\Node\CustomIcon;
use Duon\Cms\Tests\Fixtures\Node\CustomIconHandler;
use Duon\Cms\Tests\Fixtures\Node\NodeWithCustomAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithHandleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithInvalidPropertyTitleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithNameAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithPropertyTitleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRouteAttribute;
use Duon\Cms\Tests\Fixtures\Node\PlainBlock;
use Duon\Cms\Tests\Fixtures\Node\PlainPage;
use Duon\Cms\Tests\TestCase;

final class NodeSchemaRegistryTest extends TestCase
{
	// -- Registry basics -------------------------------------------------------

	public function testRegistryWithDefaultsContainsAllBuiltinHandlers(): void
	{
		$registry = Registry::withDefaults();

		$this->assertInstanceOf(HandleHandler::class, $registry->getHandler(new Handle('test')));
		$this->assertInstanceOf(LabelHandler::class, $registry->getHandler(new Label('test')));
		$this->assertInstanceOf(RouteHandler::class, $registry->getHandler(new Route('/test')));
		$this->assertInstanceOf(RenderHandler::class, $registry->getHandler(new Render('test')));
		$this->assertInstanceOf(PermissionHandler::class, $registry->getHandler(new Permission('test')));
		$this->assertInstanceOf(TitleHandler::class, $registry->getHandler(new Title('test')));
		$this->assertInstanceOf(FieldOrderHandler::class, $registry->getHandler(new FieldOrder('a', 'b')));
		$this->assertInstanceOf(DeletableHandler::class, $registry->getHandler(new Deletable()));
	}

	public function testRegistryReturnsNullForUnknownAttribute(): void
	{
		$registry = Registry::withDefaults();

		$this->assertNull($registry->getHandler(new CustomIcon('star')));
	}

	public function testRegistryAcceptsCustomHandler(): void
	{
		$registry = Registry::withDefaults();
		$registry->register(CustomIcon::class, new CustomIconHandler());

		$this->assertInstanceOf(CustomIconHandler::class, $registry->getHandler(new CustomIcon('star')));
	}

	// -- Individual handler resolve() -----------------------------------------

	public function testHandleHandlerResolve(): void
	{
		$handler = new HandleHandler();
		$result = $handler->resolve(new Handle('my-handle'), PlainPage::class);

		$this->assertEquals(['handle' => 'my-handle'], $result);
	}

	public function testLabelHandlerResolve(): void
	{
		$handler = new LabelHandler();
		$result = $handler->resolve(new Label('My Label'), PlainPage::class);

		$this->assertEquals(['label' => 'My Label'], $result);
	}

	public function testRouteHandlerResolve(): void
	{
		$handler = new RouteHandler();
		$result = $handler->resolve(new Route('/article/{uid}'), PlainPage::class);

		$this->assertEquals([
			'route' => '/article/{uid}',
			'routable' => true,
		], $result);
	}

	public function testRenderHandlerResolve(): void
	{
		$handler = new RenderHandler();
		$result = $handler->resolve(new Render('my-template'), PlainPage::class);

		$this->assertEquals([
			'renderer' => 'my-template',
			'renderable' => true,
		], $result);
	}

	public function testPermissionHandlerResolve(): void
	{
		$handler = new PermissionHandler();
		$result = $handler->resolve(new Permission(['read' => 'me']), PlainPage::class);

		$this->assertEquals(['permission' => ['read' => 'me']], $result);
	}

	public function testTitleHandlerResolve(): void
	{
		$handler = new TitleHandler();
		$result = $handler->resolve(new Title('heading'), PlainPage::class);

		$this->assertEquals(['titleField' => 'heading'], $result);
	}

	public function testTitleHandlerThrowsForEmptyClassLevelField(): void
	{
		$handler = new TitleHandler();

		$this->throws(RuntimeException::class, "The #[Title] attribute on node 'Duon\\Cms\\Tests\\Fixtures\\Node\\PlainPage' requires a non-empty field name when used on a class.");
		$handler->resolve(new Title(), PlainPage::class);
	}

	public function testFieldOrderHandlerResolve(): void
	{
		$handler = new FieldOrderHandler();
		$result = $handler->resolve(new FieldOrder('a', 'b', 'c'), PlainPage::class);

		$this->assertEquals(['fieldOrder' => ['a', 'b', 'c']], $result);
	}

	public function testDeletableHandlerResolve(): void
	{
		$handler = new DeletableHandler();
		$result = $handler->resolve(new Deletable(false), PlainPage::class);

		$this->assertEquals(['deletable' => false], $result);
	}

	// -- Schema integration with Registry -------------------------------------

	public function testSchemaResolvesBuiltinAttributesViaRegistry(): void
	{
		$registry = Registry::withDefaults();
		$schema = new Schema(PlainPage::class, $registry);

		$this->assertEquals('Plain Page', $schema->label);
		$this->assertEquals('/plain-page/{uid}', $schema->route);
		$this->assertTrue($schema->routable);
		$this->assertEquals('heading', $schema->titleField);
		$this->assertEquals(['heading', 'body'], $schema->fieldOrder);
	}

	public function testSchemaAppliesDefaultsWhenNoAttributes(): void
	{
		$registry = Registry::withDefaults();
		$schema = new Schema(NodeWithRouteAttribute::class, $registry);

		// No #[Label] => fallback to class name
		$this->assertEquals('NodeWithRouteAttribute', $schema->label);
		// No #[Handle] => fallback to kebab-case
		$this->assertEquals('node-with-route-attribute', $schema->handle);
		// No #[Permission] => default permission map
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], $schema->permission);
		// No #[Title] => null
		$this->assertNull($schema->titleField);
		// No #[FieldOrder] => null
		$this->assertNull($schema->fieldOrder);
		// No #[Deletable] => true
		$this->assertTrue($schema->deletable);
	}

	public function testSchemaResolvesTitleFromPropertyAttribute(): void
	{
		$registry = Registry::withDefaults();
		$schema = new Schema(NodeWithPropertyTitleAttribute::class, $registry);

		$this->assertEquals('heading', $schema->titleField);
	}

	public function testSchemaThrowsForPropertyTitleOnNonFieldProperty(): void
	{
		$registry = Registry::withDefaults();

		$this->throws(RuntimeException::class, "The #[Title] attribute on property 'Duon\\Cms\\Tests\\Fixtures\\Node\\NodeWithInvalidPropertyTitleAttribute::heading' requires a field-typed property.");
		new Schema(NodeWithInvalidPropertyTitleAttribute::class, $registry);
	}

	public function testSchemaDeletableFalse(): void
	{
		$registry = Registry::withDefaults();
		$schema = new Schema(PlainBlock::class, $registry);

		$this->assertFalse($schema->deletable);
	}

	public function testSchemaWithoutRegistryUsesDefaults(): void
	{
		$schema = new Schema(NodeWithNameAttribute::class);

		// Without a registry, attributes are not processed by handlers.
		// All properties fall back to defaults derived from the class name.
		$this->assertEquals('NodeWithNameAttribute', $schema->label);
		$this->assertEquals('node-with-name-attribute', $schema->handle);
	}

	// -- Custom (third-party) attribute registration --------------------------

	public function testCustomAttributeAvailableViaSchemaGet(): void
	{
		$registry = Registry::withDefaults();
		$registry->register(CustomIcon::class, new CustomIconHandler());

		$schema = new Schema(NodeWithCustomAttribute::class, $registry);

		$this->assertEquals('star', $schema->get('icon'));
	}

	public function testCustomAttributeIncludedInProperties(): void
	{
		$registry = Registry::withDefaults();
		$registry->register(CustomIcon::class, new CustomIconHandler());

		$schema = new Schema(NodeWithCustomAttribute::class, $registry);
		$props = $schema->properties();

		$this->assertArrayHasKey('icon', $props);
		$this->assertEquals('star', $props['icon']);
	}

	public function testCustomAttributeAccessibleViaTypes(): void
	{
		$registry = Registry::withDefaults();
		$registry->register(CustomIcon::class, new CustomIconHandler());

		$types = new Types($registry);

		$this->assertEquals('star', $types->get(NodeWithCustomAttribute::class, 'icon'));
		$this->assertEquals('Custom Node', $types->get(NodeWithCustomAttribute::class, 'label'));
		$this->assertTrue((bool) $types->get(NodeWithCustomAttribute::class, 'routable', false));
	}

	public function testCustomAttributeDefaultsToNull(): void
	{
		$types = new Types();

		$this->assertNull($types->get(PlainPage::class, 'icon'));
		$this->assertEquals('fallback', $types->get(PlainPage::class, 'icon', 'fallback'));
	}

	public function testUnregisteredCustomAttributeIsSilentlyIgnored(): void
	{
		$registry = Registry::withDefaults();
		// Intentionally NOT registering CustomIconHandler

		$schema = new Schema(NodeWithCustomAttribute::class, $registry);

		// Built-in attributes still resolve correctly
		$this->assertEquals('Custom Node', $schema->label);
		$this->assertTrue($schema->routable);

		// Custom attribute is just missing
		$this->assertNull($schema->get('icon'));
	}

	// -- Schema::properties() -------------------------------------------------

	public function testSchemaPropertiesContainsAllBuiltinKeys(): void
	{
		$registry = Registry::withDefaults();
		$schema = new Schema(PlainPage::class, $registry);
		$props = $schema->properties();

		$this->assertArrayHasKey('label', $props);
		$this->assertArrayHasKey('handle', $props);
		$this->assertArrayHasKey('renderer', $props);
		$this->assertArrayHasKey('routable', $props);
		$this->assertArrayHasKey('renderable', $props);
		$this->assertArrayHasKey('route', $props);
		$this->assertArrayHasKey('permission', $props);
		$this->assertArrayHasKey('titleField', $props);
		$this->assertArrayHasKey('fieldOrder', $props);
		$this->assertArrayHasKey('deletable', $props);
	}

	// -- Types with custom registry -------------------------------------------

	public function testTypesWithCustomRegistryResolvesAllAttributes(): void
	{
		$registry = Registry::withDefaults();
		$registry->register(CustomIcon::class, new CustomIconHandler());

		$types = new Types($registry);

		// Built-in resolution still works
		$this->assertEquals('plain-page', $types->get(PlainPage::class, 'handle'));
		$this->assertEquals('Plain Page', $types->get(PlainPage::class, 'label'));
		$this->assertTrue((bool) $types->get(PlainPage::class, 'routable', false));

		// Custom resolution
		$this->assertEquals('star', $types->get(NodeWithCustomAttribute::class, 'icon'));
	}

	public function testTypesCachesSchemaInstances(): void
	{
		$types = new Types();

		$schema1 = $types->schemaOf(PlainPage::class);
		$schema2 = $types->schemaOf(PlainPage::class);

		$this->assertSame($schema1, $schema2);
	}

	public function testTypesClearCacheRemovesCachedSchemas(): void
	{
		$types = new Types();

		$schema1 = $types->schemaOf(PlainPage::class);
		$types->clearCache();
		$schema2 = $types->schemaOf(PlainPage::class);

		$this->assertNotSame($schema1, $schema2);
		$this->assertEquals($schema1->handle, $schema2->handle);
	}

	// -- Derived properties ---------------------------------------------------

	public function testRenderableDefaultsToRoutable(): void
	{
		$registry = Registry::withDefaults();

		// NodeWithHandleAttribute has #[Handle] but no #[Render] or #[Route].
		// renderable defaults to routable, which is false => renderable = false
		$schema = new Schema(NodeWithHandleAttribute::class, $registry);
		$this->assertFalse($schema->renderable);
		$this->assertEquals('node-with-custom-handle-attribute', $schema->renderer);
	}

	public function testRoutableNodeIsRenderableWithoutRenderAttribute(): void
	{
		$registry = Registry::withDefaults();

		// NodeWithRouteAttribute has #[Route] but no #[Render].
		// renderable defaults to routable => true
		$schema = new Schema(NodeWithRouteAttribute::class, $registry);
		$this->assertTrue($schema->routable);
		$this->assertTrue($schema->renderable);
	}
}
