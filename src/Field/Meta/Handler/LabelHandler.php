<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Labelable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Label;

use function Duon\Cms\Field\capabilityErrorMessage;

/** @implements SchemaHandler<Label> */
class LabelHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Labelable) {
			$field->label($meta->label);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Labelable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Labelable) {
			return ['label' => $field->getLabel()];
		}

		return [];
	}
}
