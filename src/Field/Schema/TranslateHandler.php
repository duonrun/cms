<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Translatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\Translate;

class TranslateHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Translatable) {
			$field->translate(true);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Translatable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Translatable) {
			return ['translate' => $field->isTranslatable()];
		}

		return [];
	}
}
