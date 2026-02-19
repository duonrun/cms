<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\GridResizable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Meta\Columns;
use Duon\Cms\Field\Meta\MetaHandler;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

/** @implements MetaHandler<Columns> */
class ColumnsHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof GridResizable) {
			$field->columns($meta->columns, $meta->minCellWidth);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, GridResizable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof GridResizable) {
			return [
				'columns' => $field->getColumns(),
				'minCellWidth' => $field->getMinCellWidth(),
			];
		}

		return [];
	}
}
