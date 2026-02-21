<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Str;
use Duon\Sire\Shape;

class Radio extends Field
{
	public function value(): Str
	{
		return new Str($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('radio', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:radio');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
