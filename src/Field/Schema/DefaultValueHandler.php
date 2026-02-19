<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Defaultable;
use Duon\Cms\Field\Field;

class DefaultValueHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Defaultable) {
			$default = $meta->default;
			$field->default(is_callable($default) ? $default() : $default);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Defaultable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		return [];
	}
}
