<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Nodes;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Types;

abstract class Collection
{
	protected static string $name = '';
	protected static string $handle = '';
	protected static bool $showPublished = true;
	protected static bool $showLocked = false;
	protected static bool $showHidden = false;
	protected static bool $showChildren = false;

	private readonly Types $types;

	public function __construct(
		public readonly Cms $cms,
		?Types $types = null,
	) {
		$this->types = $types ?? new Types();
	}

	abstract public function entries(): Nodes;

	/** @return list<class-name> */
	public function blueprints(): array
	{
		return [];
	}

	public function name(): string
	{
		return static::$name ?: preg_replace('/(?<!^)[A-Z]/', ' $0', static::class);
	}

	/**
	 * Returns an array of columns with column definitions.
	 *
	 * Each column array must have the fields `title` and `field`
	 */
	public function columns(): array
	{
		return [
			Column::new('Titel', 'title')->bold(true),
			Column::new('Seitentyp', 'meta.name'),
			Column::new('Editor', 'meta.editor'),
			Column::new('Bearbeitet', 'meta.changed')->date(true),
			Column::new('Erstellt', 'meta.created')->date(true),
		];
	}

	public function header(): array
	{
		return array_map(function (Column $column) {
			return $column->title;
		}, $this->columns());
	}

	public function listing(): array
	{
		return $this->list();
	}

	public function list(
		int $offset = 0,
		int $limit = 50,
		string $q = '',
		string $sort = '',
		string $dir = 'desc',
		?string $parent = null,
	): array {
		$nodes = $this->entries();

		if ($this->showChildren()) {
			$parent = trim((string) $parent);

			if ($parent === '') {
				$nodes->roots();
			} else {
				$nodes->childrenOf($parent);
			}
		}

		$q = trim($q);

		if ($q !== '') {
			$nodes->search($q, $this->searchFields());
		}

		[$sort, $dir, $order] = $this->order($sort, $dir);
		$nodes->order(...$order);

		$total = $nodes->count();
		$nodes->offset($offset)->limit($limit);
		$pageNodes = iterator_to_array($nodes);

		return [
			'total' => $total,
			'offset' => $offset,
			'limit' => $limit,
			'q' => $q,
			'sort' => $sort,
			'dir' => $dir,
			'nodes' => $this->rows($pageNodes),
		];
	}

	public function searchFields(): array
	{
		return ['uid', 'title'];
	}

	public function sorts(): array
	{
		return [
			'changed' => 'changed',
			'created' => 'created',
			'uid' => 'uid',
		];
	}

	public function defaultSort(): string
	{
		return 'changed';
	}

	public function defaultDir(): string
	{
		return 'desc';
	}

	/**
	 * @param list<Node> $nodes
	 */
	private function rows(array $nodes): array
	{
		$result = [];
		$hasChildren = $this->showChildren()
			? $this->hasChildrenMap($nodes)
			: [];

		foreach ($nodes as $node) {
			$result[] = $this->row($node, $hasChildren[$node->meta->uid] ?? false);
		}

		return $result;
	}

	private function row(Node $node, bool $hasChildren): array
	{
		$columns = [];
		$parent = $node->meta->get('parent');

		if (!is_string($parent) || trim($parent) === '') {
			$parent = null;
		}

		$childBlueprints = $this->showChildren()
			? $this->childBlueprints($node)
			: [];

		foreach ($this->columns() as $column) {
			$columns[] = $column->get($node);
		}

		return [
			'uid' => $node->meta->uid,
			'published' => $node->meta->published,
			'locked' => $node->meta->locked,
			'hidden' => $node->meta->hidden,
			'parent' => $parent,
			'hasChildren' => $hasChildren,
			'childBlueprints' => $childBlueprints,
			'columns' => $columns,
		];
	}

	/**
	 * @param list<Node> $nodes
	 * @return array<string, bool>
	 */
	private function hasChildrenMap(array $nodes): array
	{
		if ($nodes === []) {
			return [];
		}

		$uids = [];

		foreach ($nodes as $node) {
			$uids[] = $node->meta->uid;
		}

		$uids = array_values(array_unique($uids));
		$list = implode(',', array_map(
			static fn(string $uid): string => "'" . str_replace("'", "\\\\'", $uid) . "'",
			$uids,
		));

		if ($list === '') {
			return [];
		}

		$children = $this->cms
			->nodes("parent @ [{$list}]")
			->published(null)
			->hidden(null);
		$result = [];

		foreach ($children as $child) {
			$parentUid = $child->meta->get('parent');

			if (is_string($parentUid) && $parentUid !== '') {
				$result[$parentUid] = true;
			}
		}

		return $result;
	}

	private function childBlueprints(Node $node): array
	{
		$children = $node->meta->type->children;

		if (!is_array($children) || $children === []) {
			return [];
		}

		$result = [];

		foreach ($children as $class) {
			if (!is_string($class) || $class === '') {
				throw new RuntimeException('The children schema must contain non-empty class names');
			}

			if (!$this->types->isNode($class)) {
				throw new RuntimeException("Unknown child node class '{$class}' in #[Children(...)]");
			}

			$result[] = [
				'slug' => (string) $this->types->get($class, 'handle'),
				'name' => (string) $this->types->get($class, 'label'),
			];
		}

		return $result;
	}

	private function order(string $sort, string $dir): array
	{
		$sort = trim($sort);
		$dir = strtolower(trim($dir));
		$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : strtolower($this->defaultDir());
		$sorts = $this->sorts();
		$sort = array_key_exists($sort, $sorts) ? $sort : $this->defaultSort();
		$field = $sorts[$sort] ?? 'changed';
		$order = [sprintf('%s %s', $field, strtoupper($dir))];

		if ($field !== 'uid') {
			$order[] = 'uid ASC';
		}

		return [$sort, $dir, $order];
	}

	public static function handle(): string
	{
		return static::$handle
			?: ltrim(
				strtolower(preg_replace(
					'/[A-Z]([A-Z](?![a-z]))*/',
					'-$0',
					basename(str_replace('\\', '/', static::class)),
				)),
				'-',
			);
	}

	public static function showPublished(): bool
	{
		return static::$showPublished;
	}

	public static function showHidden(): bool
	{
		return static::$showHidden;
	}

	public static function showLocked(): bool
	{
		return static::$showLocked;
	}

	public static function showChildren(): bool
	{
		return static::$showChildren;
	}
}
