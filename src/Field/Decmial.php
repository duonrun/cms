<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value\Decimal as DecimalValue;
use Duon\Sire\Shape;

class Decimal extends Field
{
	public function value(): DecimalValue
	{
		return new DecimalValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('decimal', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:decimal');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
