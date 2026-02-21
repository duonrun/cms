<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\DateTime as DateTimeValue;
use Duon\Sire\Shape;

class DateTime extends Field
{
	public function value(): DateTimeValue
	{
		return new DateTimeValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('datetime', $value);
	}

	public function shape(): Shape
	{
		$shape = new Shape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:datetime');
		$shape->add('value', 'text', ...$this->validators);

		return $shape;
	}
}
