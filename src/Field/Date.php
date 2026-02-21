<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Date as DateValue;
use Duon\Sire\Shape;

class Date extends Field
{
	public function value(): DateValue
	{
		return new DateValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('date', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:date');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
