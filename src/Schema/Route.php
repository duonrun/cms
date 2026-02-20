<?php

declare(strict_types=1);

namespace Duon\Cms\Schema;

use Attribute;

#[Attribute]
class Route
{
	/**
	 * @param string|array<string,string> $value
	 */
	public function __construct(public readonly array|string $value) {}
}
