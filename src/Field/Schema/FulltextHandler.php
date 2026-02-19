<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Searchable;
use Duon\Cms\Field\Field;

class FulltextHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Searchable) {
			$field->fulltext($meta->fulltextWeight);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Searchable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		return [];
	}
}
