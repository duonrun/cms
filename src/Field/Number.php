<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value\Number as NumberValue;
use Duon\Sire\Shape;

class Number extends Field
{
	public function value(): NumberValue
	{
		return new NumberValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('number', $value);
	}

	public function shape(): Shape
	{
		$shape = new Shape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:number');
		$shape->add('value', 'float', ...$this->validators);

		return $shape;
	}
}
