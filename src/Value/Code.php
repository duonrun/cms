<?php

declare(strict_types=1);

namespace Duon\Cms\Value;

use Duon\Cms\Field\Capability\Syntaxable;
use Duon\Cms\Field\Capability\Translatable;
use Duon\Cms\Field\Field;

/**
 * @property-read Field&Translatable&Syntaxable $field
 */
class Code extends Text
{
	public function syntax(): string
	{
		$syntax = $this->data['syntax'] ?? null;

		if (is_string($syntax) && $syntax !== '') {
			return $syntax;
		}

		return $this->field->getDefaultSyntax();
	}
}
