<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Describable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\Description;
use Duon\Cms\Schema\MetaHandler;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<Description> */
class DescriptionHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Describable) {
			$field->description($meta->description);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Describable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Describable) {
			return ['description' => $field->getDescription()];
		}

		return [];
	}
}
