<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Validatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Validate;

use function Duon\Cms\Field\capabilityErrorMessage;

/** @implements SchemaHandler<Validate> */
class ValidateHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Validatable) {
			$field->addValidators(...$meta->validators);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Validatable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Validatable) {
			return ['validators' => $field->validators()];
		}

		return [];
	}
}
