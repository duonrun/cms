<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Hidable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\SchemaHandler;
use Duon\Cms\Schema\Hidden;

use function Duon\Cms\Field\capabilityErrorMessage;

/** @implements SchemaHandler<Hidden> */
class HiddenHandler implements SchemaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Hidable) {
			$field->hidden(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Hidable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Hidable) {
			return ['hidden' => $field->getHidden()];
		}

		return [];
	}
}
