<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Translatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Meta\MetaHandler;
use Duon\Cms\Field\Meta\Translate;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

/** @implements MetaHandler<Translate> */
class TranslateHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Translatable) {
			$field->translate(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Translatable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Translatable) {
			return ['translate' => $field->isTranslatable()];
		}

		return [];
	}
}
