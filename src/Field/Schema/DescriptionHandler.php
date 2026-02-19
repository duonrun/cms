<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Describable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Description;

use function Duon\Cms\Field\capabilityErrorMessage;

class DescriptionHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Describable) {
			$field->description($meta->description);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Describable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Describable) {
			return ['description' => $field->getDescription()];
		}

		return [];
	}
}
