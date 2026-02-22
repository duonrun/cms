<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Syntaxable;
use Duon\Cms\Field\Field;

class SyntaxHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Syntaxable) {
			$field->syntaxes($meta->syntaxes);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Syntaxable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Syntaxable) {
			return ['syntaxes' => $field->getSyntaxes()];
		}

		return [];
	}
}
