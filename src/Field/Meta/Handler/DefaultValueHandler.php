<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Defaultable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\DefaultValue;

use function Duon\Cms\Field\capabilityErrorMessage;

/** @implements SchemaHandler<DefaultValue> */
class DefaultValueHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Defaultable) {
			$default = $meta->default;
			$field->default(is_callable($default) ? $default() : $default);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Defaultable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		return [];
	}
}
