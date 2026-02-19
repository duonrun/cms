<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;

interface SchemaHandler
{
	public function apply(object $meta, Field $field): void;

	public function properties(object $meta, Field $field): array;
}
