<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Requirable;
use Duon\Cms\Field\Field;

class RequiredHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Requirable) {
			$field->required(true);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Requirable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Requirable) {
			return ['required' => $field->isRequired()];
		}

		return [];
	}
}
