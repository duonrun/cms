<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Menu;
use Duon\Cms\Finder\Node;
use Duon\Cms\Finder\Nodes;
use Duon\Cms\Finder\Render;
use Duon\Cms\Node\Factory;

/**
 * @psalm-property-read Nodes $nodes
 * @psalm-property-read Node $node
 * @psalm-property-read Menu $menu
 */
class Cms
{
	private readonly Factory $nodeFactory;

	public function __construct(private readonly Context $context)
	{
		$this->nodeFactory = new Factory($context->registry);
	}

	public function __get($key): Nodes|Node|Menu
	{
		return match ($key) {
			'nodes' => new Nodes($this->context, $this, $this->nodeFactory),
			'node' => new Node($this->context, $this, $this->nodeFactory),
			default => throw new RuntimeException('Property not supported'),
		};
	}

	public function nodes(
		string $query = '',
	): Nodes {
		return (new Nodes($this->context, $this, $this->nodeFactory))->filter($query);
	}

	public function node(
		string $query,
		array $types = [],
		int $limit = 0,
		string $order = '',
	): array {
		return (new Node($this->context, $this, $this->nodeFactory))->find($query, $types, $limit, $order);
	}

	public function menu(string $menu): Menu
	{
		return new Menu($this->context, $menu);
	}

	public function render(
		string $uid,
		array $templateContext = [],
		?bool $deleted = false,
		?bool $published = true,
	): Render {
		return new Render($this->context, $this, $this->nodeFactory, $uid, $templateContext, $deleted, $published);
	}

	public function nodeFactory(): Factory
	{
		return $this->nodeFactory;
	}
}
