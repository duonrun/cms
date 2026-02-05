<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Finder\Finder;
use Duon\Cms\Finder\Nodes;

abstract class Collection
{
	protected static string $name = '';
	protected static string $handle = '';
	protected static bool $showPublished = true;
	protected static bool $showLocked = false;
	protected static bool $showHidden = false;

	public function __construct(
		public readonly Finder $find,
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
		$result = [];

		foreach ($this->entries() as $node) {
			$columns = [];

			foreach ($this->columns() as $column) {
				$columns[] = $column->get($node);
			}

			$result[] = [
				'uid' => $node->meta('uid'),
				'published' => $node->meta('published'),
				'locked' => $node->meta('locked'),
				'hidden' => $node->meta('hidden'),
				'columns' => $columns,
			];
		}

		return $result;
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
