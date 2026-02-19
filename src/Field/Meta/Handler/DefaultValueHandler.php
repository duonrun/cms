<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Defaultable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\DefaultValue;
use Duon\Cms\Schema\MetaHandler;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<DefaultValue> */
class DefaultValueHandler implements MetaHandler
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
