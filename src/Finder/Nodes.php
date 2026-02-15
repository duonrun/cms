<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\NodeFactory;
use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Node\NodeProxy;
use Generator;
use Iterator;

final class Nodes implements Iterator
{
	private string $whereFields = '';
	private string $whereTypes = '';
	private string $order = '';
	private ?int $limit = null;
	private ?bool $deleted = false; // defaults to false, if all nodes are needed set $deleted to null
	private ?bool $published = true; // ditto
	private ?bool $hidden = false; // ditto
	private readonly array $builtins;
	private Generator $result;

	public function __construct(
		private readonly Context $context,
		private readonly Finder $find,
		private readonly NodeFactory $nodeFactory,
	) {
		$this->builtins = [
			'changed' => 'n.changed',
			'created' => 'n.created',
			'creator' => 'uc.uid',
			'editor' => 'ue.uid',
			'deleted' => 'n.deleted',
			'id' => 'n.uid',
			'locked' => 'n.locked',
			'published' => 'n.published',
			'hidden' => 'n.hidden',
			'type' => 't.handle',
			'handle' => 't.handle',
			'uid' => 'n.uid',
			'kind' => 't.kind',
		];
	}

	public function filter(string $query): self
	{
		$compiler = new QueryCompiler($this->context, $this->builtins);
		$this->whereFields = $compiler->compile($query);

		return $this;
	}

	public function types(string ...$types): self
	{
		$this->whereTypes = $this->typesCondition($types);

		return $this;
	}

	public function type(string $type): self
	{
		$this->whereTypes = $this->typesCondition([$type]);

		return $this;
	}

	public function order(string ...$order): self
	{
		$compiler = new OrderCompiler($this->builtins);
		$this->order = $compiler->compile(implode(',', $order));

		return $this;
	}

	public function limit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	public function published(?bool $published): self
	{
		$this->published = $published;

		return $this;
	}

	public function hidden(?bool $hidden): self
	{
		$this->hidden = $hidden;

		return $this;
	}

	public function deleted(?bool $deleted): self
	{
		$this->deleted = $deleted;

		return $this;
	}

	public function rewind(): void
	{
		if (!isset($this->result)) {
			$this->fetchResult();
		}
		$this->result->rewind();
	}

	public function current(): NodeProxy
	{
		if (!isset($this->result)) {
			$this->fetchResult();
		}

		$page = $this->result->current();

		$page['content'] = json_decode($page['content'], true);
		$page['editor_data'] = json_decode($page['editor_data'], true);
		$page['creator_data'] = json_decode($page['creator_data'], true);
		$page['paths'] = json_decode($page['paths'], true);
		$class = $this->context
			->registry
			->tag(Cms::NODE_TAG)
			->entry($page['handle'])
			->definition();

		$node = $this->nodeFactory->create($class, $this->context, $this->find, $page);

		return $this->nodeFactory->proxy($node, $this->context->request);
	}

	public function key(): int
	{
		return $this->result->key();
	}

	public function next(): void
	{
		$this->result->next();
	}

	public function valid(): bool
	{
		return $this->result->valid();
	}

	private function fetchResult(): void
	{
		$conditions = implode(' AND ', array_filter([
			trim($this->whereFields),
			trim($this->whereTypes),
		], fn($clause) => !empty($clause)));

		$params = [
			'condition' => $conditions,
			'limit' => $this->limit,
		];

		if (is_bool($this->deleted)) {
			$params['deleted'] = $this->deleted;
		}

		if (is_bool($this->published)) {
			$params['published'] = $this->published;
		}

		if (is_bool($this->hidden)) {
			$params['hidden'] = $this->hidden;
		}

		if ($this->order) {
			$params['order'] = $this->order;
		}

		$this->result = $this->context->db->nodes->find($params)->lazy();
	}

	private function typesCondition(array $types): string
	{
		$result = [];

		foreach ($types as $type) {
			if (class_exists($type)) {
				$type = NodeMeta::handle($type);
			}

			$result[] = 't.handle = ' . $this->context->db->quote($type);
		}

		return match (count($result)) {
			0 => '',
			1 => '    ' . $result[0],
			default => "    (\n        "
				. implode("\n        OR ", $result)
				. "\n    )",
		};
	}
}
