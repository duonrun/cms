<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Context;
use Duon\Cms\Finder\Dialect\SqlDialectFactory;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\Node;
use Generator;
use Iterator;

final class Nodes implements Iterator
{
	private CompiledQuery $whereFields;
	private CompiledQuery $whereTypes;
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

		$this->whereFields = new CompiledQuery('', []);
		$this->whereTypes = new CompiledQuery('', []);
	}

	public function filter(string $query): self
	{
		$compiler = new QueryCompiler($this->context, $this->builtins);
		$this->whereFields = $compiler->compile($query, 'f');

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
		$dialect = SqlDialectFactory::fromDriver($this->context->db->getPdoDriver());
		$compiler = new OrderCompiler($dialect, $this->builtins);
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

	public function current(): Node
	{
		if (!isset($this->result)) {
			$this->fetchResult();
		}

		$page = $this->result->current();

		$page['content'] = json_decode($page['content'], true);
		$page['editor_data'] = json_decode($page['editor_data'], true);
		$page['creator_data'] = json_decode($page['creator_data'], true);
		$page['paths'] = json_decode($page['paths'], true);
		$context = $this->context;
		$class = $context
			->registry
			->tag(Node::class)
			->entry($page['handle'])
			->definition();

		return new $class($context, $this->find, $page);
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
		$conditions = [];
		$params = [
			'limit' => $this->limit,
		];

		foreach ([$this->whereFields, $this->whereTypes] as $compiled) {
			if (trim($compiled->sql) !== '') {
				$conditions[] = $compiled->sql;
				$params = array_merge($params, $compiled->params);
			}
		}

		$params['condition'] = implode(' AND ', $conditions);

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

	private function typesCondition(array $types): CompiledQuery
	{
		$result = [];
		$params = new QueryParams('t');

		foreach ($types as $type) {
			if (class_exists($type)) {
				$type = $type::handle();
			}

			$result[] = 't.handle = ' . $params->add($type);
		}

		$sql = match (count($result)) {
			0 => '',
			1 => '    ' . $result[0],
			default => "    (\n        "
				. implode("\n        OR ", $result)
				. "\n    )",
		};

		return new CompiledQuery($sql, $params->all());
	}
}
