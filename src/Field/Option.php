<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value;
use Duon\Sire\Shape;

class Option extends Field implements Capability\Selectable
{
	use Capability\IsSelectable;

	protected bool $hasLabel = false;

	public function value(): Value\Option
	{
		return new Value\Option($this->owner, $this, $this->valueContext);
	}

	public function properties(): array
	{
		$result = parent::properties();
		$result['hasLabel'] = $this->hasLabel;

		return $result;
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('option', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:option');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
