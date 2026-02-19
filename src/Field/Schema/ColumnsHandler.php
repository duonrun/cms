<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\GridResizable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\Columns;

class ColumnsHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof GridResizable) {
			$field->columns($meta->columns, $meta->minCellWidth);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, GridResizable::class));
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
