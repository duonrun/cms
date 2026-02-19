<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Searchable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Fulltext;

use function Duon\Cms\Field\capabilityErrorMessage;

class FulltextHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Searchable) {
			$field->fulltext($meta->fulltextWeight);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Searchable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		return [];
	}
}
