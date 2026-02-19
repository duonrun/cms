<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Immutable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\Immutable as ImmutableMeta;

class ImmutableHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Immutable) {
			$field->immutable(true);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Immutable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Immutable) {
			return ['immutable' => $field->getImmutable()];
		}

		return [];
	}
}
