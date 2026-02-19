<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Field\Field;

abstract class Handler
{
	abstract public function apply(object $meta, Field $field): void;

	abstract public function properties(object $meta, Field $field): array;

	/** @param class-string $capabilityClass */
	protected function capabilityErrorMessage(Field $field, string $capabilityClass): string
	{
		$fieldType = $field::class;
		$nodeType = $field->owner::class;

		return "The field \"{$field->name}\" (type: {$fieldType}) of node {$nodeType} "
			. "cannot be used with the capability {$capabilityClass}";
	}
}
