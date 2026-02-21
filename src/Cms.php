<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Menu;
use Duon\Cms\Finder\Node;
use Duon\Cms\Finder\Nodes;
use Duon\Cms\Finder\Render;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Types;

/**
 * @psalm-property-read Nodes $nodes
 * @psalm-property-read Node $node
 * @psalm-property-read Menu $menu
 */
class Cms
{
	private readonly Factory $nodeFactory;
	private readonly Types $types;

	public function __construct(
		private readonly Context $context,
		Types $types,
	) {
		$this->types = $types;
		$this->nodeFactory = new Factory($context->registry, types: $this->types);
	}

	public function __get($key): Nodes|Node|Menu
	{
		return match ($key) {
			'nodes' => new Nodes($this->context, $this, $this->nodeFactory, $this->types),
			'node' => new Node($this->context, $this, $this->nodeFactory, $this->types),
			default => throw new RuntimeException('Property not supported'),
		};
	}

	public function nodes(
		string $query = '',
	): Nodes {
		return (new Nodes($this->context, $this, $this->nodeFactory, $this->types))->filter($query);
	}

	public function node(
		string $query,
		array $types = [],
		int $limit = 0,
		string $order = '',
	): array {
		return (new Node($this->context, $this, $this->nodeFactory, $this->types))->find($query, $types, $limit, $order);
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
		return new Render($this->context, $this, $this->nodeFactory, $this->types, $uid, $templateContext, $deleted, $published);
	}

	public function nodeFactory(): Factory
	{
		return $this->nodeFactory;
	}
}
