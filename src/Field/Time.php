<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Time as TimeValue;
use Duon\Sire\Schema;

class Time extends Field
{
	public function value(): TimeValue
	{
		return new TimeValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('time', $value);
	}

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:time');
		$schema->add('value', 'text', ...$this->validators);

		return $schema;
	}
}
