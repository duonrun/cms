<?php

declare(strict_types=1);

namespace Duon\Cms\Schema;

use Duon\Cms\Field\Field;

interface MetaHandler
{
	public function apply(object $meta, Field $field): void;

	public function properties(object $meta, Field $field): array;
}
