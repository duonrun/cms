<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Resizable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Rows;

use function Duon\Cms\Field\capabilityErrorMessage;

/** @implements SchemaHandler<Rows> */
class RowsHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Resizable) {
			$field->rows($meta->rows);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Resizable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Resizable) {
			return ['rows' => $field->getRows()];
		}

		return [];
	}
}
