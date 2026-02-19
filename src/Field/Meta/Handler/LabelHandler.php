<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Labelable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\MetaHandler;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

/** @implements MetaHandler<Label> */
class LabelHandler implements MetaHandler
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
