<?php

declare(strict_types=1);

namespace Duon\Cms;

use Closure;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Meta;
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
		private readonly ?Meta $meta = null,
	) {}

	public static function new(
		string|Closure $title,
		string|Closure $field,
		?Meta $meta = null,
	): self {
		return new self($title, $field, $meta);
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

	public function get(object $node): array
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

	private function getValue(object $node, string $field): mixed
	{
		$inner = Node::unwrap($node);

		switch ($field) {
			case 'title':
				if ($inner instanceof Title) {
					return $inner->title();
				}

				return method_exists($inner, 'title') ? $inner->title() : '';
			case 'meta.name':
				$meta = $this->meta ?? new Meta();

				return $meta->label($inner::class);
			case 'meta.uid':
			case 'meta.published':
			case 'meta.hidden':
			case 'meta.locked':
			case 'meta.created':
			case 'meta.changed':
			case 'meta.deleted':
			case 'meta.content':
			case 'meta.handle':
				return Factory::meta($node, explode('.', $field)[1]);
			case 'meta.class':
				return $inner::class;
			case 'meta.classname':
				return basename(str_replace('\\', '/', $inner::class));
			case 'meta.editor':
				return escape(
					Factory::meta($node, 'editor_data')['name']
					?? Factory::meta($node, 'editor_username'),
				) ?? Factory::meta($node, 'editor_email');
			case 'meta.creator':
				return escape(
					Factory::meta($node, 'creator_data')['name']
					?? Factory::meta($node, 'creator_username'),
				) ?? Factory::meta($node, 'creator_email');
			default:
				$hydrator = new FieldHydrator();
				$fieldObj = $hydrator->getField($inner, $field);
				$value = $fieldObj->value();

				return $value->isset() ? $value : null;
		}
	}
}
