<?php

declare(strict_types=1);

namespace Duon\Cms\Schema;

use Attribute;

#[Attribute]
class Name
{
	public function __construct(public readonly string $value) {}
}
