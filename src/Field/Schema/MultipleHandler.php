<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\AllowsMultiple;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Multiple;

use function Duon\Cms\Field\capabilityErrorMessage;

class MultipleHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof AllowsMultiple) {
			$field->multiple(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, AllowsMultiple::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof AllowsMultiple) {
			return ['multiple' => $field->getMultiple()];
		}

		return [];
	}
}
