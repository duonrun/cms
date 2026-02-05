<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Context;
use Duon\Cms\Node\Node;
use Generator;
use Iterator;

final class Nodes implements Iterator
{
	private ?CompiledQuery $filterQuery = null;
	private ?CompiledQuery $typesQuery = null;
	private string $order = '';
	private ?int $limit = null;
	private ?bool $deleted = false;
	private ?bool $published = true;
	private ?bool $hidden = false;
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
	}

	public function filter(string $query): self
	{
		$compiler = new QueryCompiler($this->context, $this->builtins);
		$this->filterQuery = $compiler->compile($query);

		return $this;
	}

	public function types(string ...$types): self
	{
		$this->typesQuery = $this->typesCondition($types);

		return $this;
	}

	public function type(string $type): self
	{
		$this->typesQuery = $this->typesCondition([$type]);

		return $this;
	}

	public function order(string ...$order): self
	{
		$compiler = new OrderCompiler($this->context->dialect(), $this->builtins);
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
		// Build condition SQL
		$conditionParts = [];
		$filterParams = [];

		if ($this->filterQuery !== null && $this->filterQuery->sql !== '') {
			$conditionParts[] = trim($this->filterQuery->sql);
			$filterParams = array_merge($filterParams, $this->filterQuery->params);
		}

		if ($this->typesQuery !== null && $this->typesQuery->sql !== '') {
			$conditionParts[] = trim($this->typesQuery->sql);
			$filterParams = array_merge($filterParams, $this->typesQuery->params);
		}

		$conditions = implode(' AND ', $conditionParts);

		// Build query params for Quma
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

		// Merge filter parameters into the query params
		$params = array_merge($params, $filterParams);

		$this->result = $this->context->db->nodes->find($params)->lazy();
	}

	/**
	 * @param array<string> $types
	 */
	private function typesCondition(array $types): CompiledQuery
	{
		if (count($types) === 0) {
			return CompiledQuery::empty();
		}

		$params = [];
		$conditions = [];
		$paramIndex = 0;

		// Use 't' prefix for type params to avoid collision with filter params
		foreach ($types as $type) {
			if (class_exists($type)) {
				$type = $type::handle();
			}

			$paramName = 'type' . $paramIndex++;
			$params[$paramName] = $type;
			$conditions[] = 't.handle = :' . $paramName;
		}

		$sql = match (count($conditions)) {
			1 => '    ' . $conditions[0],
			default => "    (\n        "
				. implode("\n        OR ", $conditions)
				. "\n    )",
		};

		return new CompiledQuery($sql, $params);
	}
}
