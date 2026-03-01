<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Finder\Nodes;
use Duon\Cms\Node\Node;

abstract class Collection
{
	protected static string $name = '';
	protected static string $handle = '';
	protected static bool $showPublished = true;
	protected static bool $showLocked = false;
	protected static bool $showHidden = false;

	public function __construct(
		public readonly Cms $cms,
	) {}

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
	): array {
		$nodes = $this->entries();

		$q = trim($q);

		if ($q !== '') {
			$nodes->search($q, $this->searchFields());
		}

		[$sort, $dir, $order] = $this->order($sort, $dir);
		$nodes->order(...$order);

		$total = $nodes->count();
		$nodes->offset($offset)->limit($limit);

		return [
			'total' => $total,
			'offset' => $offset,
			'limit' => $limit,
			'q' => $q,
			'sort' => $sort,
			'dir' => $dir,
			'nodes' => $this->rows($nodes),
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

	private function rows(Nodes $nodes): array
	{
		$result = [];

		foreach ($nodes as $node) {
			$result[] = $this->row($node);
		}

		return $result;
	}

	private function row(Node $node): array
	{
		$columns = [];

		foreach ($this->columns() as $column) {
			$columns[] = $column->get($node);
		}

		return [
			'uid' => $node->meta->uid,
			'published' => $node->meta->published,
			'locked' => $node->meta->locked,
			'hidden' => $node->meta->hidden,
			'columns' => $columns,
		];
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
}
