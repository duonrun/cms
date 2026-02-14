<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\DateTime as DateTimeValue;
use Duon\Sire\Schema;

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

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:datetime');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
