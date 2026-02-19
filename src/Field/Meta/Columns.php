<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Columns
{
	public function __construct(
		public int $columns,
		public int $minCellWidth = 1,
	) {}
}
