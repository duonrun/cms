<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\SyntaxAware;
use Duon\Cms\Field\Field;

class SyntaxHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof SyntaxAware) {
			$field->syntaxes($meta->syntaxes);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, SyntaxAware::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof SyntaxAware) {
			return ['syntaxes' => $field->getSyntaxes()];
		}

		return [];
	}
}
