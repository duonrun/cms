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
	public function testLabelAttributeSet(): void
	{
		$this->assertEquals('NodeWithHandleAttribute', Meta::label(NodeWithHandleAttribute::class));
		$this->assertEquals('Node With Custom Name Attribute', Meta::label(NodeWithNameAttribute::class));
	}

	public function testHandleAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', Meta::handle(NodeWithNameAttribute::class));
		$this->assertEquals('node-with-custom-handle-attribute', Meta::handle(NodeWithHandleAttribute::class));
	}

	public function testRouteAttributeSet(): void
	{
		$this->assertEquals('', Meta::route(NodeWithNameAttribute::class));
		$this->assertEquals('/node-with-custom/{route}', Meta::route(NodeWithRouteAttribute::class));
	}

	public function testRoutableIsDerivedFromRouteAttribute(): void
	{
		$this->assertFalse(Meta::routable(NodeWithNameAttribute::class));
		$this->assertTrue(Meta::routable(NodeWithRouteAttribute::class));
	}

	public function testRenderAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', Meta::forClass(NodeWithNameAttribute::class)->renderer);
		$this->assertEquals('template-defined-by-render-attribute', Meta::forClass(NodeWithRenderAttribute::class)->renderer);
	}

	public function testRenderableUsesRenderAttributeOrHandleFallback(): void
	{
		$this->assertTrue(Meta::renderable(NodeWithNameAttribute::class));
		$this->assertTrue(Meta::renderable(NodeWithRenderAttribute::class));
	}

	public function testPermissionAttributeSet(): void
	{
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], Meta::forClass(NodeWithNameAttribute::class)->permission);
		$this->assertEquals([
			'read' => 'me',
		], Meta::forClass(NodeWithPermissionAttribute::class)->permission);
	}
}
