<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\AllowsMultiple;
use Duon\Cms\Field\Capability\Limitable;
use Duon\Cms\Field\Field;

class MultipleHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof AllowsMultiple) {
			$field->multiple(true);

			return;
		}

		if ($field instanceof Limitable) {
			$field->limit(999);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, AllowsMultiple::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof AllowsMultiple) {
			return ['multiple' => $field->getMultiple()];
		}

		if ($field instanceof Limitable) {
			return ['multiple' => $field->getLimitMax() > 1];
		}

		return [];
	}
}
