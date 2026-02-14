<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Title
{
	public function __construct(public readonly string $field) {}
}
