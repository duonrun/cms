<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Selectable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\MetaHandler;
use Duon\Cms\Schema\Options;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<Options> */
class OptionsHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Selectable) {
			$field->options($meta->options);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Selectable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Selectable) {
			return ['options' => $field->getOptions()];
		}

		return [];
	}
}
