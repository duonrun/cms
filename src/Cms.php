<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Menu;
use Duon\Cms\Finder\Node;
use Duon\Cms\Finder\Nodes;
use Duon\Cms\Finder\Render;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Meta;

/**
 * @psalm-property-read Nodes $nodes
 * @psalm-property-read Node $node
 * @psalm-property-read Menu $menu
 */
class Cms
{
	private readonly Factory $nodeFactory;
	private readonly Meta $meta;

	public function __construct(
		private readonly Context $context,
		?Meta $meta = null,
	) {
		$this->meta = $meta ?? new Meta();
		$this->nodeFactory = new Factory($context->registry, meta: $this->meta);
	}

	public function __get($key): Nodes|Node|Menu
	{
		return match ($key) {
			'nodes' => new Nodes($this->context, $this, $this->nodeFactory, $this->meta),
			'node' => new Node($this->context, $this, $this->nodeFactory, $this->meta),
			default => throw new RuntimeException('Property not supported'),
		};
	}

	public function nodes(
		string $query = '',
	): Nodes {
		return (new Nodes($this->context, $this, $this->nodeFactory, $this->meta))->filter($query);
	}

	public function node(
		string $query,
		array $types = [],
		int $limit = 0,
		string $order = '',
	): array {
		return (new Node($this->context, $this, $this->nodeFactory, $this->meta))->find($query, $types, $limit, $order);
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
		return new Render($this->context, $this, $this->nodeFactory, $this->meta, $uid, $templateContext, $deleted, $published);
	}

	public function nodeFactory(): Factory
	{
		return $this->nodeFactory;
	}
}
