<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Node\Meta;
use Duon\Cms\Tests\Fixtures\Node\NodeWithHandleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithNameAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithPermissionAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRenderAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRouteAttribute;
use Duon\Cms\Tests\TestCase;

final class MetaTest extends TestCase
{
	private Meta $meta;

	protected function setUp(): void
	{
		parent::setUp();
		$this->meta = new Meta();
	}

	public function testLabelAttributeSet(): void
	{
		$this->assertEquals('NodeWithHandleAttribute', $this->meta->label(NodeWithHandleAttribute::class));
		$this->assertEquals('Node With Custom Name Attribute', $this->meta->label(NodeWithNameAttribute::class));
	}

	public function testHandleAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->meta->handle(NodeWithNameAttribute::class));
		$this->assertEquals('node-with-custom-handle-attribute', $this->meta->handle(NodeWithHandleAttribute::class));
	}

	public function testRouteAttributeSet(): void
	{
		$this->assertEquals('', $this->meta->route(NodeWithNameAttribute::class));
		$this->assertEquals('/node-with-custom/{route}', $this->meta->route(NodeWithRouteAttribute::class));
	}

	public function testRoutableIsDerivedFromRouteAttribute(): void
	{
		$this->assertFalse($this->meta->routable(NodeWithNameAttribute::class));
		$this->assertTrue($this->meta->routable(NodeWithRouteAttribute::class));
	}

	public function testRenderAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', $this->meta->forClass(NodeWithNameAttribute::class)->renderer);
		$this->assertEquals('template-defined-by-render-attribute', $this->meta->forClass(NodeWithRenderAttribute::class)->renderer);
	}

	public function testRenderableUsesRenderAttributeOrHandleFallback(): void
	{
		$this->assertTrue($this->meta->renderable(NodeWithNameAttribute::class));
		$this->assertTrue($this->meta->renderable(NodeWithRenderAttribute::class));
	}

	public function testPermissionAttributeSet(): void
	{
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], $this->meta->forClass(NodeWithNameAttribute::class)->permission);
		$this->assertEquals([
			'read' => 'me',
		], $this->meta->forClass(NodeWithPermissionAttribute::class)->permission);
	}
}
