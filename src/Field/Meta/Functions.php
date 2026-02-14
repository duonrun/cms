<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta;

use Duon\Cms\Field\Field;

/** @param class-string $capabilityClass */
function capabilityErrorMessage(Field $field, string $capabilityClass): string
{
	$fieldType = $field::class;
	$nodeType = $field->owner::class;

	return "The field \"{$field->name}\" (type: {$fieldType}) of node {$nodeType} "
		. "cannot be used with the capability {$capabilityClass}";
}
