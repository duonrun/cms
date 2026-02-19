<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Describable;
use Duon\Cms\Field\Field;

class DescriptionHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Describable) {
			$field->description($meta->description);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Describable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Describable) {
			return ['description' => $field->getDescription()];
		}

		return [];
	}
}
