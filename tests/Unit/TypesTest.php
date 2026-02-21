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
		$this->assertEquals('NodeWithHandleAttribute', $this->types->label(NodeWithHandleAttribute::class));
		$this->assertEquals('Node With Custom Name Attribute', $this->types->label(NodeWithNameAttribute::class));
	}

	public function testHandleAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->types->handle(NodeWithNameAttribute::class));
		$this->assertEquals('node-with-custom-handle-attribute', $this->types->handle(NodeWithHandleAttribute::class));
	}

	public function testRouteAttributeSet(): void
	{
		$this->assertEquals('', $this->types->route(NodeWithNameAttribute::class));
		$this->assertEquals('/node-with-custom/{route}', $this->types->route(NodeWithRouteAttribute::class));
	}

	public function testRoutableIsDerivedFromRouteAttribute(): void
	{
		$this->assertFalse($this->types->routable(NodeWithNameAttribute::class));
		$this->assertTrue($this->types->routable(NodeWithRouteAttribute::class));
	}

	public function testRenderAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->types->forClass(NodeWithNameAttribute::class)->renderer);
		$this->assertEquals('template-defined-by-render-attribute', $this->types->forClass(NodeWithRenderAttribute::class)->renderer);
	}

	public function testRenderableUsesRenderAttributeOrHandleFallback(): void
	{
		$this->assertTrue($this->types->renderable(NodeWithNameAttribute::class));
		$this->assertTrue($this->types->renderable(NodeWithRenderAttribute::class));
	}

	public function testPermissionAttributeSet(): void
	{
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], $this->types->forClass(NodeWithNameAttribute::class)->permission);
		$this->assertEquals([
			'read' => 'me',
		], $this->types->forClass(NodeWithPermissionAttribute::class)->permission);
	}
}
