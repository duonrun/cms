<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Condition;

final readonly class TokenPart implements Part
{
	public function __construct(public string $sql) {}
}
