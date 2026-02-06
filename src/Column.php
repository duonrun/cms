<?php

declare(strict_types=1);

namespace Duon\Cms;

use Closure;
use Duon\Cms\Node\Node;

use function Duon\Cms\Util\escape;

final class Column
{
	private bool|Closure $bold = false;
	private bool|Closure $italic = false;
	private bool|Closure $badge = false;
	private bool $date = false;
	private string|Closure $color = '';

	public function __construct(
		public readonly string $title,
		public readonly string|Closure $field,
	) {}

	public static function new(
		string|Closure $title,
		string|Closure $field,
	): self {
		return new self($title, $field);
	}

	public function bold(bool|Closure $bold): self
	{
		$this->bold = $bold;

		return $this;
	}

	public function italic(bool|Closure $italic): self
	{
		$this->italic = $italic;

		return $this;
	}

	public function badge(bool|Closure $badge): self
	{
		$this->badge = $badge;

		return $this;
	}

	public function date(bool|Closure $date): self
	{
		$this->date = $date;

		return $this;
	}

	public function get(Node $node): array
	{
		return [
			'value' => is_string($this->field)
				? $this->getValue($node, $this->field)
				: ($this->field)($node),
			'bold' => is_bool($this->bold) ? $this->bold : ($this->bold)($node),
			'italic' => is_bool($this->italic) ? $this->italic : ($this->italic)($node),
			'badge' => is_bool($this->badge) ? $this->badge : ($this->badge)($node),
			'date' => is_bool($this->date) ? $this->date : ($this->date)($node),
			'color' => is_string($this->color) ? $this->color : ($this->color)($node),
		];
	}

	private function getValue(Node $node, string $field): mixed
	{
		switch ($field) {
			case 'title':
				return $node->title();
			case 'meta.name':
				return $node->name();
			case 'meta.uid':
			case 'meta.published':
			case 'meta.hidden':
			case 'meta.locked':
			case 'meta.created':
			case 'meta.changed':
			case 'meta.deleted':
			case 'meta.content':
			case 'meta.handle':
				return $node->meta(explode('.', $field)[1]);
			case 'meta.class':
				return $node::class;
			case 'meta.classname':
				return $node::className();
			case 'meta.editor':
				return escape(
					$node->meta('editor_data')['name']
					?? $node->meta('editor_username'),
				) ?? $node->meta('editor_email');
			case 'meta.creator':
				return escape(
					$node->meta('creator_data')['name']
					?? $node->meta('creator_username'),
				) ?? $node->meta('creator_email');
			default:
				return (string) $node->getValue($field);
		}
	}
}
