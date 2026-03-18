<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Db\Dialect;
use Duon\Cms\Db\Dialects;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Types;
use Duon\Cms\Plugin;
use Generator;
use Iterator;

final class Nodes implements Iterator
{
	use CompilesField;

	private QueryState $state;
	private readonly Dialect $dialect;
	private readonly array $builtins;
	private readonly NodeRecordMapper $records;
	private Generator $result;

	public function __construct(
		private readonly Context $context,
		private readonly Cms $cms,
		private readonly Factory $nodeFactory,
		private readonly Types $types,
	) {
		$this->state = QueryState::defaults();
		$this->dialect = Dialects::for($this->context->db);
		$this->records = new NodeRecordMapper($this->context, $this->cms, $this->nodeFactory);
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
			'parent' => '(SELECT p.uid FROM ' . $this->dialect->table('nodes') . ' p WHERE p.node = n.parent)',
			'routable' => $this->typeFlagExpression(
				fn(string $class): bool => (bool) $this->types->get($class, 'routable', false),
			),
			'renderable' => $this->typeFlagExpression(
				fn(string $class): bool => (bool) $this->types->get($class, 'renderable', false),
			),
			'type' => 't.handle',
			'handle' => 't.handle',
			'uid' => 'n.uid',
		];
	}

	public function filter(string $query): self
	{
		$compiler = new QueryCompiler($this->context, $this->builtins);
		$this->state = $this->state->withFilters(
			$this->state->filters->and($compiler->compileFragment($query)),
		);

		return $this;
	}

	public function search(string $query, array $fields): self
	{
		$query = trim($query);

		if ($query === '') {
			return $this;
		}

		$fields = array_values(array_filter(array_map('trim', $fields)));

		if ($fields === []) {
			return $this;
		}

		$terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

		if (!is_array($terms) || $terms === []) {
			return $this;
		}

		$expressions = array_map(
			fn(string $field): string => $this->fieldExpression($field),
			$fields,
		);
		$termClauses = [];

		foreach ($terms as $term) {
			$needle = $this->context->db->quote('%' . $term . '%');
			$fieldClauses = array_map(
				fn(string $expression): string => $this->dialect->compileSearchMatch($expression, $needle),
				$expressions,
			);

			$termClauses[] = '(' . implode(' OR ', $fieldClauses) . ')';
		}

		$this->addWhere(new SqlFragment(implode(' AND ', $termClauses)));

		return $this;
	}

	public function types(string ...$types): self
	{
		$this->state = $this->state->withTypes($this->typesCondition($types));

		return $this;
	}

	public function type(string $type): self
	{
		$this->state = $this->state->withTypes($this->typesCondition([$type]));

		return $this;
	}

	public function roots(): self
	{
		$this->addWhere(new SqlFragment('n.parent IS NULL'));

		return $this;
	}

	public function childrenOf(string $uid): self
	{
		$uid = trim($uid);

		if ($uid === '') {
			throw new RuntimeException('Parent uid is required');
		}

		$param = 'parent_uid_' . count($this->state->condition()->params);

		$this->addWhere(new SqlFragment(
			'n.parent = (SELECT p.node FROM ' . $this->dialect->table('nodes') . ' p WHERE p.uid = :' . $param . ')',
			[$param => $uid],
		));

		return $this;
	}

	/** @param list<string> $uids */
	public function childrenOfAny(array $uids): self
	{
		$uids = array_values(array_filter(array_map(
			static fn(string $uid): string => trim($uid),
			$uids,
		), static fn(string $uid): bool => $uid !== ''));

		if ($uids === []) {
			return $this;
		}

		$params = [];
		$placeholders = [];
		$offset = count($this->state->condition()->params);

		foreach ($uids as $index => $uid) {
			$key = 'parent_uid_' . ($offset + $index);
			$params[$key] = $uid;
			$placeholders[] = ':' . $key;
		}

		$this->addWhere(new SqlFragment(
			'(SELECT p.uid FROM ' . $this->dialect->table('nodes') . ' p WHERE p.node = n.parent) IN (' . implode(', ', $placeholders) . ')',
			$params,
		));

		return $this;
	}

	public function order(string ...$order): self
	{
		$compiler = new OrderCompiler($this->builtins, $this->dialect);
		$this->state = $this->state->withOrder($compiler->compile(implode(',', $order)));

		return $this;
	}

	public function limit(int $limit): self
	{
		$this->state = $this->state->withLimit($limit);

		return $this;
	}

	public function offset(int $offset): self
	{
		$this->state = $this->state->withOffset($offset);

		return $this;
	}

	public function count(): int
	{
		$record = $this->context->db->nodes->count($this->state->baseParams())->one();

		return (int) ($record['count'] ?? 0);
	}

	public function published(?bool $published): self
	{
		$this->state = $this->state->withPublished($published);

		return $this;
	}

	public function hidden(?bool $hidden): self
	{
		$this->state = $this->state->withHidden($hidden);

		return $this;
	}

	public function deleted(?bool $deleted): self
	{
		$this->state = $this->state->withDeleted($deleted);

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

		return $this->records->proxy($this->result->current());
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
		$this->result = $this->context->db->nodes->find($this->state->findParams())->lazy();
	}

	private function addWhere(SqlFragment $fragment): void
	{
		if ($fragment->isEmpty()) {
			return;
		}

		$this->state = $this->state->withFilters($this->state->filters->and($fragment));
	}

	private function fieldExpression(string $field): string
	{
		$builtin = $this->builtins[$field] ?? null;

		if (is_string($builtin) && $builtin !== '') {
			return $builtin;
		}

		if (!preg_match('/^[A-Za-z][A-Za-z0-9._-]*$/', $field)) {
			throw new RuntimeException('Invalid field name for search: ' . $field);
		}

		return $this->compileField($field, 'n.content');
	}

	private function typesCondition(array $types): SqlFragment
	{
		$result = [];

		foreach ($types as $type) {
			if (class_exists($type)) {
				$type = (string) $this->types->get($type, 'handle');
			}

			$result[] = 't.handle = ' . $this->context->db->quote($type);
		}

		return new SqlFragment(match (count($result)) {
			0 => '',
			1 => '    ' . $result[0],
			default => "    (\n        "
				. implode("\n        OR ", $result)
				. "\n    )",
		});
	}

	private function typeFlagExpression(callable $flag): string
	{
		$handles = [];
		$types = $this->context->container->tag(Plugin::NODE_TAG);

		foreach ($types->entries() as $handle) {
			$class = $types->entry($handle)->definition();

			if (!is_string($class) || !class_exists($class) || !$flag($class)) {
				continue;
			}

			$handles[] = $this->context->db->quote($handle);
		}

		sort($handles);

		if ($handles === []) {
			return 'FALSE';
		}

		return 't.handle IN (' . implode(', ', $handles) . ')';
	}

	protected function dialect(): Dialect
	{
		return $this->dialect;
	}
}
