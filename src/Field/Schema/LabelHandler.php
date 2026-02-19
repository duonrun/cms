<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Labelable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\Label;

class LabelHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Labelable) {
			$field->label($meta->label);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Labelable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Labelable) {
			return ['label' => $field->getLabel()];
		}

		return [];
	}
}
