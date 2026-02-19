<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Requirable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\MetaHandler;
use Duon\Cms\Schema\Required;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<Required> */
class RequiredHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Requirable) {
			$field->required(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Requirable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Requirable) {
			return ['required' => $field->isRequired()];
		}

		return [];
	}
}
