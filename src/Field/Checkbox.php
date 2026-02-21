<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Boolean;
use Duon\Sire\Shape;

class Checkbox extends Field
{
	public function value(): Boolean
	{
		return new Boolean($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('checkbox', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:checkbox');
		$schema->add('value', 'bool', ...$this->validators);

		return $schema;
	}
}
