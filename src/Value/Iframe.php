<?php

declare(strict_types=1);

namespace Duon\Cms\Value;

use Duon\Cms\Field\Capability\Translatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Node\Node;

use function Duon\Cms\Util\escape;

/**
 * @property-read Field&Translatable $field
 */
class Iframe extends Value
{
	protected string $value;

	public function __construct(
		Node $node,
		Field&Translatable $field,
		ValueContext $context,
		protected int $index = 0,
	) {
		parent::__construct($node, $field, $context);
	}

	public function __toString(): string
	{
		return escape($this->unwrap());
	}

	public function unwrap(): string
	{
		if (isset($this->value)) {
			return $this->value;
		}

		if ($this->field->isTranslatable()) {
			$locale = $this->locale;

			while ($locale) {
				$value = $this->data['value'][$locale->id] ?? null;

				if ($value) {
					$this->value = $value;

					return $value;
				}

				$locale = $locale->fallback();
			}

			$this->value = '';

			return '';
		}

		$this->value = isset($this->data['value'])
			? $this->data['value'] : '';

		return $this->value;
	}

	public function json(): mixed
	{
		return $this->unwrap();
	}

	public function isset(): bool
	{
		return $this->unwrap() ?? null ? true : false;
	}
}
