<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Immutable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Meta\Immutable as ImmutableMeta;
use Duon\Cms\Field\Meta\MetaHandler;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

/** @implements MetaHandler<ImmutableMeta> */
class ImmutableHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Immutable) {
			$field->immutable(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Immutable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Immutable) {
			return ['immutable' => $field->getImmutable()];
		}

		return [];
	}
}
