<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Tests\Fixtures\Node\NodeWithHandleAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithNameAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithPermissionAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRenderAttribute;
use Duon\Cms\Tests\Fixtures\Node\NodeWithRouteAttribute;
use Duon\Cms\Tests\TestCase;

final class NodeMetaTest extends TestCase
{
	public function testNameAttributeSet(): void
	{
		$this->assertEquals('NodeWithHandleAttribute', NodeMeta::name(NodeWithHandleAttribute::class));
		$this->assertEquals('Node With Custom Name Attribute', NodeMeta::name(NodeWithNameAttribute::class));
	}

	public function testHandleAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', NodeMeta::handle(NodeWithNameAttribute::class));
		$this->assertEquals('node-with-custom-handle-attribute', NodeMeta::handle(NodeWithHandleAttribute::class));
	}

	public function testRouteAttributeSet(): void
	{
		$this->assertEquals('', NodeMeta::route(NodeWithNameAttribute::class));
		$this->assertEquals('/node-with-custom/{route}', NodeMeta::route(NodeWithRouteAttribute::class));
	}

	public function testRoutableIsDerivedFromRouteAttribute(): void
	{
		$this->assertFalse(NodeMeta::routable(NodeWithNameAttribute::class));
		$this->assertTrue(NodeMeta::routable(NodeWithRouteAttribute::class));
	}

	public function testRenderAttributeSet(): void
	{
		$this->assertEquals('node-with-name-attribute', NodeMeta::forClass(NodeWithNameAttribute::class)->renderer);
		$this->assertEquals('template-defined-by-render-attribute', NodeMeta::forClass(NodeWithRenderAttribute::class)->renderer);
	}

	public function testRenderableUsesRenderAttributeOrHandleFallback(): void
	{
		$this->assertTrue(NodeMeta::renderable(NodeWithNameAttribute::class));
		$this->assertTrue(NodeMeta::renderable(NodeWithRenderAttribute::class));
	}

	public function testPermissionAttributeSet(): void
	{
		$this->assertEquals([
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		], NodeMeta::forClass(NodeWithNameAttribute::class)->permission);
		$this->assertEquals([
			'read' => 'me',
		], NodeMeta::forClass(NodeWithPermissionAttribute::class)->permission);
	}
}
