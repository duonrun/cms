<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Searchable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Meta\Fulltext;
use Duon\Cms\Field\Meta\MetaHandler;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

/** @implements MetaHandler<Fulltext> */
class FulltextHandler implements MetaHandler
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
