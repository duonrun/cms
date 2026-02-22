<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Node\Types;
use Duon\Cms\Tests\Fixtures\Node\NodeWithHandleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithNameAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithPermissionAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRenderAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRouteAttribute;
use Duon\Cms\Tests\TestCase;

final class TypesTest extends TestCase
{
	private Types $types;

	protected function setUp(): void
	{
		parent::setUp();
		$this->types = new Types();
	}

	public function testLabelAttributeSet(): void
	{
		$this->assertEquals('NodeWithHandleAttribute', $this->types->get(NodeWithHandleAttribute::class, 'label'));
		$this->assertEquals('Node With Custom Name Attribute', $this->types->get(NodeWithNameAttribute::class, 'label'));
	}

	public function testHandleAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->types->get(NodeWithNameAttribute::class, 'handle'));
		$this->assertEquals('node-with-custom-handle-attribute', $this->types->get(NodeWithHandleAttribute::class, 'handle'));
	}

	public function testRouteAttributeSet(): void
	{
		$this->assertEquals('', $this->types->get(NodeWithNameAttribute::class, 'route'));
		$this->assertEquals('/node-with-custom/{route}', $this->types->get(NodeWithRouteAttribute::class, 'route'));
	}

	public function testRoutableIsDerivedFromRouteAttribute(): void
	{
		$this->assertFalse((bool) $this->types->get(NodeWithNameAttribute::class, 'routable', false));
		$this->assertTrue((bool) $this->types->get(NodeWithRouteAttribute::class, 'routable', false));
	}

	public function testRenderAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->types->schemaOf(NodeWithNameAttribute::class)->renderer);
		$this->assertEquals('template-defined-by-render-attribute', $this->types->schemaOf(NodeWithRenderAttribute::class)->renderer);
	}

	public function testRenderableUsesRenderAttributeOrHandleFallback(): void
	{
		$this->assertTrue((bool) $this->types->get(NodeWithNameAttribute::class, 'renderable', false));
		$this->assertTrue((bool) $this->types->get(NodeWithRenderAttribute::class, 'renderable', false));
	}

	public function testPermissionAttributeSet(): void
	{
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], $this->types->schemaOf(NodeWithNameAttribute::class)->permission);
		$this->assertEquals([
			'read' => 'me',
		], $this->types->schemaOf(NodeWithPermissionAttribute::class)->permission);
	}
}
