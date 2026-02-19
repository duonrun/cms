<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Resizable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\MetaHandler;
use Duon\Cms\Schema\Width;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<Width> */
class WidthHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Resizable) {
			$field->width($meta->width);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Resizable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Resizable) {
			return ['width' => $field->getWidth()];
		}

		return [];
	}
}
